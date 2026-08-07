import urllib.request
import urllib.parse
import http.cookiejar
import json
import re
import uuid

def main():
    cj = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

    # 1. GET /admin/login
    resp = opener.open('http://127.0.0.1:8000/admin/login')
    html = resp.read().decode('utf-8')

    match = re.search(r'name="_token"\s+value="([^"]+)"', html)
    token = match.group(1) if match else None

    print('Login CSRF token obtained:', bool(token))

    # 2. POST login form
    login_payload = urllib.parse.urlencode({
        '_token': token,
        'email': 'admin@dosttv.com',
        'password': 'password'
    }).encode('utf-8')

    req = urllib.request.Request('http://127.0.0.1:8000/admin/login', data=login_payload, method='POST')
    try:
        resp = opener.open(req)
        print('Login HTTP status:', resp.status, '| Final URL:', resp.geturl())
    except Exception as e:
        print('Login Exception:', str(e))

    # 3. GET /admin/announcements/create to obtain page CSRF token
    req = urllib.request.Request('http://127.0.0.1:8000/admin/announcements/create')
    resp = opener.open(req)
    page_html = resp.read().decode('utf-8')

    match = re.search(r'name="csrf-token"\s+content="([^"]+)"', page_html)
    csrf_token = match.group(1) if match else token

    # 4. Upload 100 KB JPG file to /livewire/upload-file
    boundary = f'----WebKitFormBoundary{uuid.uuid4().hex}'
    file_bytes = b'\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00' + (b'\x00' * 102400) + b'\xFF\xD9'

    body = []
    body.append(f'--{boundary}'.encode('utf-8'))
    body.append(b'Content-Disposition: form-data; name="files[]"; filename="live_test_100kb.jpg"')
    body.append(b'Content-Type: image/jpeg')
    body.append(b'')
    body.append(file_bytes)
    body.append(f'--{boundary}--'.encode('utf-8'))
    body.append(b'')

    payload = b'\r\n'.join(body)

    upload_req = urllib.request.Request(
        'http://127.0.0.1:8000/livewire/upload-file',
        data=payload,
        headers={
            'Content-Type': f'multipart/form-data; boundary={boundary}',
            'Accept': 'application/json',
            'X-Livewire': 'true',
            'X-CSRF-TOKEN': csrf_token
        },
        method='POST'
    )

    try:
        resp = opener.open(upload_req)
        print('Upload HTTP Status:', resp.status)
        print('Upload Response Body:', resp.read().decode('utf-8'))
    except urllib.error.HTTPError as e:
        print('Upload HTTP Error:', e.code)
        err_body = e.read().decode('utf-8')
        print('Error Lines:', '\n'.join(err_body.splitlines()[:20]))
    except Exception as e:
        print('Upload Exception:', str(e))

if __name__ == '__main__':
    main()
