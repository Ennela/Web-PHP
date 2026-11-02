#!/usr/bin/env python3
"""Upload regression checks with temporary customer/admin/product/blog records.

Set MYSQLPASSWORD and optional MYSQLHOST/MYSQLPORT/MYSQLUSER/MYSQLDATABASE,
then run python3 scripts/test_security_uploads.py against the local application.
Test records and files are removed in finally; no emails or payments are sent.
"""
import urllib.request, urllib.error, urllib.parse, http.cookiejar, re, subprocess, os, uuid, struct, zlib, json
from pathlib import Path
ROOT=Path(__file__).resolve().parent.parent
BASE=os.environ.get('CSRF_BASE_URL', 'http://127.0.0.1:8000/').rstrip('/')+'/'
if not os.environ.get('MYSQLPASSWORD'):
    raise SystemExit('Set MYSQLPASSWORD for the temporary test database fixtures.')
SQL=['mysql','--protocol=TCP','-h',os.environ.get('MYSQLHOST','127.0.0.1'),'-P',os.environ.get('MYSQLPORT','3306'),'-u',os.environ.get('MYSQLUSER','root'),os.environ.get('MYSQLDATABASE','giaythethao2'),'-N','-e']
name='security_upload_'+uuid.uuid4().hex[:12]
pw=uuid.uuid4().hex
saved=[]
product_ids=[]
blog_ids=[]
def sql(q): return subprocess.check_output(SQL+[q],text=True,env=dict(os.environ,MYSQL_PWD=os.environ['MYSQLPASSWORD'])).strip()
def chunk(kind,data): return struct.pack('>I',len(data))+kind+data+struct.pack('>I',zlib.crc32(kind+data)&0xffffffff)
png=b'\x89PNG\r\n\x1a\n'+chunk(b'IHDR',struct.pack('>IIBBBBB',1,1,8,2,0,0,0))+chunk(b'IDAT',zlib.compress(b'\x00\xff\x00\x00'))+chunk(b'IEND',b'')
class Client:
    def __init__(self):self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    def req(self,path,data=None,headers=None):
        try:
            with self.opener.open(urllib.request.Request(BASE+path,data=data,headers=headers or {})) as r:return r.status,r.read().decode(errors='replace')
        except urllib.error.HTTPError as e:return e.code,e.read().decode(errors='replace')
    def form(self,path,fields):return self.req(path,urllib.parse.urlencode(fields).encode())
    def multipart(self,path,fields,files):
        boundary='----test'+uuid.uuid4().hex
        parts=[]
        for k,v in fields.items():parts.extend([('--'+boundary+'\r\nContent-Disposition: form-data; name="'+k+'"\r\n\r\n'+str(v)+'\r\n').encode()])
        for k,filename,content,mime in files:
            parts.append(('--'+boundary+'\r\nContent-Disposition: form-data; name="'+k+'"; filename="'+filename+'"\r\nContent-Type: '+mime+'\r\n\r\n').encode()+content+b'\r\n')
        parts.append(('--'+boundary+'--\r\n').encode())
        return self.req(path,b''.join(parts),{'Content-Type':'multipart/form-data; boundary='+boundary})
def token(body):
    m=re.search(r'name="(?:csrf-token|csrf_token)" (?:content|value)="([^"]+)"',body)
    assert m,body[:500]
    return m.group(1)
def assert_clean(status,body):
    assert status==200,(status,body[:200])
    assert not any(t in body for t in ['Fatal error','Parse error','Stack trace:']),body[-400:]
def register_saved(path):
    saved.append(ROOT/path)
    assert re.fullmatch(r'(?:admin/uploads|auth/uploads/avatars)/[0-9a-f]{32}\.png',path),path
    assert (ROOT/path).read_bytes()==png,path
try:
    sql(f"INSERT INTO tbl_tkkhachhang(username,password,hoten,email) VALUES('{name}','{pw}','Upload check','{name}@example.invalid')")
    customer_id=int(sql(f"SELECT makh FROM tbl_tkkhachhang WHERE username='{name}'"))
    sql(f"INSERT INTO tbl_qlthanhvien(taikhoan,matkhau,hoten) VALUES('{name}','{pw}','Upload check')")
    admin_id=int(sql(f"SELECT id FROM tbl_qlthanhvien WHERE taikhoan='{name}'"))
    client=Client()
    status,body=client.req('auth/dangnhap.php'); assert_clean(status,body)
    status,body=client.form('auth/dangnhap.php',dict(dangnhap='1',taikhoan=name,matkhau=pw,csrf_token=token(body)));assert_clean(status,body)
    csrf=token(body)
    status,body=client.multipart('auth/api_upload_avatar.php',{'csrf_token':csrf},[('avatar','valid.png',png,'image/png')])
    assert_clean(status,body); result=json.loads(body); assert result['success'],result
    avatar=result['avatar_url'].removeprefix(BASE);register_saved(avatar)
    print('Valid avatar saved with random PNG filename')
    for filename,content in [('shell.php',png),('shell.phtml',png),('shell.php.png',png),('fake.png',b'<?php echo 123; ?>'),('wrong.jpg',png),('too-large.png',png+b'x'*(2*1024*1024))]:
        status,body=client.multipart('auth/api_upload_avatar.php',{'csrf_token':csrf},[('avatar',filename,content,'image/png')])
        assert status==422,(filename,status,body[:200])
        assert sql(f'SELECT avatar FROM tbl_tkkhachhang WHERE makh={customer_id}')==avatar
        print('Rejected avatar',filename,status)
    admin=Client();status,body=admin.req('admin/canhbao.php');assert_clean(status,body)
    status,body=admin.form('admin/canhbao.php',dict(dangnhap='1',taikhoan=name,matkhau=pw,csrf_token=token(body)));assert_clean(status,body)
    status,body=admin.req('admin/themsanpham.php');assert_clean(status,body);csrf=token(body)
    product=dict({'btn-save':'1','csrf_token':csrf,'tensp':name,'giasanpham':'1000','giagoc':'2000','noidung':'Upload integration','nhomsp':'Test'})
    status,body=admin.multipart('admin/themsanpham.php',product,[('anhdaidien','valid.png',png,'image/png'),('anhgiuoithieu1','shell.php',b'<?php echo 123; ?>','image/png')])
    assert status==422,(status,body[:250])
    assert sql(f"SELECT COUNT(*) FROM tbl_qlsanpham WHERE tensp='{name}'")=='0'
    print('Invalid secondary image blocks complete product submission')
    files=[('anhdaidien','main.png',png,'image/png'),('anhgiuoithieu1','gallery.png',png,'image/png')]
    status,body=admin.multipart('admin/themsanpham.php',product,files);assert_clean(status,body)
    product_id=int(sql(f"SELECT masp FROM tbl_qlsanpham WHERE tensp='{name}'"));product_ids.append(product_id)
    paths=sql(f'SELECT anhdaidien,anhgiuoithieu1 FROM tbl_qlsanpham WHERE masp={product_id}').split('\t')
    for p in paths:register_saved('admin/'+p)
    print('Valid product primary/gallery saved with random safe names')
    status,body=admin.req(f'admin/edit_qlsanpham.php?masp={product_id}');assert_clean(status,body)
    status,body=admin.multipart(f'admin/edit_qlsanpham.php?masp={product_id}',product,[('anhdaidien','wrong.jpg',png,'image/jpeg')])
    assert status==422,(status,body[:250])
    assert sql(f'SELECT anhdaidien FROM tbl_qlsanpham WHERE masp={product_id}')==paths[0]
    print('Invalid product edit keeps previous image')
    status,body=admin.multipart(f'admin/edit_qlsanpham.php?masp={product_id}',product,[('anhdaidien','replacement.png',png,'image/png')]);assert_clean(status,body)
    register_saved('admin/'+sql(f'SELECT anhdaidien FROM tbl_qlsanpham WHERE masp={product_id}'))
    print('Valid product edit image saved')
    status,body=admin.req('admin/thembaidang.php');assert_clean(status,body)
    blog=dict({'btn-save':'1','csrf_token':csrf,'tieude':name,'nguoiphutrach':str(admin_id),'chedo':'public','noidung':'Upload integration'})
    status,body=admin.multipart('admin/thembaidang.php',blog,files);assert_clean(status,body)
    blog_id=int(sql(f"SELECT id FROM tbl_qlbaidang WHERE tieude='{name}'"));blog_ids.append(blog_id)
    paths=sql(f'SELECT anhdaidien,anhgiuoithieu1 FROM tbl_qlbaidang WHERE id={blog_id}').split('\t')
    for p in paths:register_saved('admin/'+p)
    status,body=admin.req(f'admin/edit_qlbaidang.php?id={blog_id}');assert_clean(status,body)
    status,body=admin.multipart(f'admin/edit_qlbaidang.php?id={blog_id}',blog,[('anhdaidien','shell.php.png',png,'image/png')]);assert status==422,(status,body[:200])
    assert sql(f'SELECT anhdaidien FROM tbl_qlbaidang WHERE id={blog_id}')==paths[0]
    print('Blog primary/gallery upload valid; dangerous edit rejected')
    status,body=admin.multipart(f'admin/edit_qlbaidang.php?id={blog_id}',blog,[('anhdaidien','replacement.png',png,'image/png')]);assert_clean(status,body)
    register_saved('admin/'+sql(f'SELECT anhdaidien FROM tbl_qlbaidang WHERE id={blog_id}'))
    print('Valid blog edit image saved')
finally:
    sql(f"DELETE FROM tbl_qlsanpham WHERE tensp='{name}'")
    sql(f"DELETE FROM tbl_qlbaidang WHERE tieude='{name}'")
    sql(f"DELETE FROM tbl_tkkhachhang WHERE username='{name}'")
    sql(f"DELETE FROM tbl_qlthanhvien WHERE taikhoan='{name}'")
    for path in saved:path.unlink(missing_ok=True)
    print('Temporary test records and saved images removed')
