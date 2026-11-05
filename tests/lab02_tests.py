import requests, re, json, sys
B="http://localhost:8000"
def sess(): 
    s=requests.Session(); return s
def tok(s,url="/index.php"):
    r=s.get(B+url); return re.search(r'name="csrf_token" value="([0-9a-f]+)"',r.text).group(1)
def book(s,wid,name,email,qty,t=None):
    t=t or tok(s,"/booking.php?workshop_id="+wid)
    return s.post(B+"/booking.php",data=dict(csrf_token=t,workshop_id=wid,full_name=name,email=email,quantity=qty),allow_redirects=False)
def bookings(s):
    r=s.get(B+"/dashboard.php"); return re.findall(r'<td>\s*(BK-[0-9A-F]{6})\s*</td>',r.text), r.text
res={}
def rec(k,v): res[k]=v; print(k,"=>",v)

# ---------- TC (Instruction)
s=sess()
r=s.get(B+"/index.php?q=PHP"); rec("TC01",("PHP Web Essentials" in r.text, "Practical AI" not in r.text))
r=s.get(B+"/index.php?category=AI"); rec("TC02",("Practical AI Automation" in r.text,"PHP Web Essentials" not in r.text,"UX Design" not in r.text))
r=s.get(B+"/index.php?q=xyz"); rec("TC01b_empty","No workshops matched" in r.text)
r=book(s,"php","Nguyen Van A","a@example.com","2"); rec("TC03_status",(r.status_code,r.headers.get("Location")))
refs,html=bookings(s); rec("TC03",(len(refs),"900,000 VND" in html))
s2=sess(); t=tok(s2,"/booking.php?workshop_id=php")
r=book(s2,"php","X Y","bad-email","1",t); rec("TC04_status",(r.status_code,r.headers.get("Location")))
rr=s2.get(B+"/booking.php?workshop_id=php"); rec("TC04",("Invalid email format." in rr.text, len(bookings(s2)[0])==0))
for q in ["0","5","1.5","-1"]:
    r=book(s2,"php","Valid Name","ok@example.com",q,t); rr=s2.get(B+"/booking.php?workshop_id=php")
    rec("TC05_qty_"+q,(r.status_code,"Quantity must be an integer from 1 to 4." in rr.text,len(bookings(s2)[0])))
r=book(s2,"hack","Valid Name","ok@example.com","1",t); rr=s2.get(B+"/booking.php?workshop_id=hack"); rec("TC06",(r.status_code,"Please select an existing workshop." in rr.text,len(bookings(s2)[0])))
r=s2.post(B+"/booking.php",data=dict(workshop_id="php",full_name="Valid",email="ok@example.com",quantity="1"),allow_redirects=False); rec("TC09_no_token",r.status_code)
r=s2.post(B+"/booking.php",data=dict(csrf_token="deadbeef",workshop_id="php",full_name="Valid",email="ok@example.com",quantity="1"),allow_redirects=False); rec("TC09_bad_token",r.status_code)
r=s2.post(B+"/preference.php",data=dict(theme="dark"),allow_redirects=False); rec("TC09_pref_no_token",r.status_code)
# TC07 refresh dashboard
n1=len(bookings(s)[0]); bookings(s); n2=len(bookings(s)[0]); rec("TC07",(n1,n2))
# TC08 theme
t=tok(s); r=s.post(B+"/preference.php",data=dict(csrf_token=t,theme="dark"),allow_redirects=False)
rec("TC08",(r.status_code,[c for c in r.headers.get("Set-Cookie","").split(", ")][:1], 'class="dark"' in s.get(B+"/index.php").text))
r=s.post(B+"/preference.php",data=dict(csrf_token=t,theme="neon"),allow_redirects=False); rec("TC08_invalid",r.status_code)
r=s.get(B+"/preference.php"); rec("preference_GET",r.status_code)
r=s.get(B+"/logout.php"); rec("logout_GET",r.status_code)
# XSS
s3=sess(); r=book(s3,"php","<b>Mai</b>  Tran","m@example.com","1"); refs,html=bookings(s3); rec("XSS",("<b>" not in html, "Mai Tran" in html))
# TC10 clear
t=tok(s); r=s.post(B+"/logout.php",data=dict(csrf_token=t),allow_redirects=False); rec("TC10",(r.status_code,r.headers.get("Location"),len(bookings(s)[0]),'class="dark"' in s.get(B+"/index.php").text))
# strlen byte vs char
r=book(s3,"php",("Nguyễn Thị Hương "*7).strip(),"u@example.com","1"); rr=s3.get(B+"/booking.php?workshop_id=php"); rec("unicode_len",("2–120 bytes" in rr.text or "Name must have" in rr.text))

# ---------- Lab02 T01..T09
s=sess()
book(s,"php","Nguyen Van A","a@example.com","2"); book(s,"ai","Tran Thi B","b@example.com","1")
refs,html=bookings(s); rA,rB=refs
def snap(): 
    return s.get(B+"/dashboard.php").text
# T01
r=s.get(B+"/booking_detail.php?ref="+rA); rec("T01",(r.status_code,all(x in r.text for x in [rA,"Nguyen Van A","a@example.com","PHP Web Essentials","450,000 VND","900,000 VND"]),"Back to My Bookings" in r.text,"booking_edit.php?ref="+rA in r.text))
# T02 / D02
for u in ["/booking_detail.php?ref=BK-ZZZZZZ","/booking_detail.php","/booking_detail.php?ref[]=x","/booking_detail.php?ref=0","/booking_detail.php?ref=%3Cscript%3Ealert(1)%3C/script%3E"]:
    r=s.get(B+u); rec("T02 "+u,(r.status_code,"Booking not found" in r.text,"Warning" not in r.text and "Notice" not in r.text and "Fatal" not in r.text,"<script>alert" not in r.text))
# D05
before=snap(); [s.get(B+"/booking_detail.php?ref="+rA) for _ in range(3)]; rec("D05",before==snap())
# T03 edit 2->3
r=s.get(B+"/booking_edit.php?ref="+rA); rec("E01_prefill",(r.status_code,'value="Nguyen Van A"' in r.text,'value="a@example.com"' in r.text,'value="2"' in r.text,"Cancel" in r.text))
t=tok(s,"/booking_edit.php?ref="+rA)
bB=s.get(B+"/booking_detail.php?ref="+rB).text
# forged fields too
r=s.post(B+"/booking_edit.php?ref="+rA,data=dict(csrf_token=t,full_name="Nguyen Van A2",email="a2@example.com",quantity="3",price="1",workshop_id="ai",total="1",amount="1",unit_fee="1",ref=rB),allow_redirects=False)
d=s.get(B+"/booking_detail.php?ref="+rA).text
rec("T03",(r.status_code,r.headers.get("Location"),"1,350,000 VND" in d,"PHP Web Essentials" in d,"Nguyen Van A2" in d,"450,000 VND" in d))
dash=snap(); rec("flash_after_update","updated successfully" in dash)
rec("T08",(bB==s.get(B+"/booking_detail.php?ref="+rB).text, len(bookings(s)[0])))
# T07 refresh
before=snap(); r=s.get(B+"/dashboard.php"); rec("T07",(before.count("BK-")>0, len(bookings(s)[0]), "1,350,000" in snap()))
# T04 bad email
before=snap()
r=s.post(B+"/booking_edit.php?ref="+rA,data=dict(csrf_token=t,full_name="Hacker",email="not-an-email",quantity="2"),allow_redirects=False)
rec("T04",(r.status_code,"Invalid email format." in r.text,'value="Hacker"' in r.text,'value="not-an-email"' in r.text,before==snap()))
# T05
for q in ["0","5","1.5","-1","abc",""]:
    before=snap(); r=s.post(B+"/booking_edit.php?ref="+rA,data=dict(csrf_token=t,full_name="Valid Name",email="v@example.com",quantity=q),allow_redirects=False)
    rec("T05 qty="+repr(q),(r.status_code,"Quantity must be an integer" in r.text,before==snap()))
# E03 name
for nm in ["","A"]:
    r=s.post(B+"/booking_edit.php?ref="+rA,data=dict(csrf_token=t,full_name=nm,email="v@example.com",quantity="2"),allow_redirects=False); rec("E03 name="+repr(nm),(r.status_code,re.findall(r'class="error">([^<]+)<',r.text)))
# seats ai qty 4 ok ; 
# T06 CSRF
before=snap()
r=s.post(B+"/booking_edit.php?ref="+rA,data=dict(full_name="CSRF",email="c@example.com",quantity="1"),allow_redirects=False); rec("T06_missing",(r.status_code,before==snap()))
r=s.post(B+"/booking_edit.php?ref="+rA,data=dict(csrf_token="bad",full_name="CSRF",email="c@example.com",quantity="1"),allow_redirects=False); rec("T06_wrong",(r.status_code,before==snap()))
# GET cannot mutate
r=s.get(B+"/booking_edit.php?ref="+rA+"&full_name=Hack&email=h@x.com&quantity=4"); rec("E02_GET",(r.status_code,before==snap()))
r=s.put(B+"/booking_edit.php?ref="+rA); rec("E02_PUT",r.status_code)
# E08 unknown ref POST
r=s.post(B+"/booking_edit.php?ref=BK-NOPE00",data=dict(csrf_token=t,full_name="New Guy",email="n@example.com",quantity="1"),allow_redirects=False); rec("E08_post",(r.status_code,"Booking not found" in r.text,before==snap(),len(bookings(s)[0])))
r=s.get(B+"/booking_edit.php?ref=BK-NOPE00"); rec("E08_get",(r.status_code,"Booking not found" in r.text))
r=s.get(B+"/booking_edit.php"); rec("E08_noref",(r.status_code,"Booking not found" in r.text))
# XSS in edit
r=s.post(B+"/booking_edit.php?ref="+rA,data=dict(csrf_token=t,full_name='"><script>alert(1)</script>',email="x@example.com",quantity="1"),allow_redirects=False)
d=s.get(B+"/booking_detail.php?ref="+rA).text; rec("D03",("<script" not in d, "&quot;" in d, r.status_code))
rec("D03_name",re.findall(r'Full name</dt><dd>(.*?)</dd>',d))
# T09 private browser
p=sess(); r=p.get(B+"/booking_detail.php?ref="+rA); rec("T09",(r.status_code,"Booking not found" in r.text,"Nguyen Van A" not in r.text and "@example.com" not in r.text))
# 2.7 session expired: edit after clear
t2=tok(s); s.post(B+"/logout.php",data=dict(csrf_token=t2),allow_redirects=False)
r=s.get(B+"/booking_edit.php?ref="+rA); rec("2.7_after_clear",(r.status_code,"Booking not found" in r.text))
r=s.post(B+"/booking_edit.php?ref="+rA,data=dict(csrf_token=t,full_name="Late",email="l@example.com",quantity="1"),allow_redirects=False); rec("2.7_post_old_token",r.status_code)
json.dump(res,open("tests/results.json","w"),default=str,indent=1)
