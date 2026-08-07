import os
import sys
import json
import urllib.request
import urllib.parse
import http.cookiejar
import uuid
import re

def get_log_lines():
    log_path = 'storage/logs/laravel.log'
    if not os.path.exists(log_path):
        return []
    with open(log_path, 'r', encoding='utf-8', errors='ignore') as f:
        return f.readlines()

def create_dummy_jpeg(size_in_bytes):
    header = b'\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00'
    footer = b'\xFF\xD9'
    padding_needed = size_in_bytes - len(header) - len(footer)
    if padding_needed > 0:
        return header + (b'\x00' * padding_needed) + footer
    else:
        return header + footer

def get_csrf_and_cookies(base_url):
    cj = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))
    req = urllib.request.Request(base_url)
    resp = opener.open(req)
    html = resp.read().decode('utf-8', errors='ignore')
    
    csrf_token = None
    match = re.search(r'name="csrf-token"\s+content="([^"]+)"', html)
    if match:
        csrf_token = match.group(1)
    
    # Also look for XSRF-TOKEN in cookies
    xsrf_token = None
    for cookie in cj:
        if cookie.name == 'XSRF-TOKEN':
            xsrf_token = urllib.parse.unquote(cookie.value)
            
    return opener, cj, csrf_token or xsrf_token

def test_upload(opener, url, csrf_token, file_bytes, filename):
    boundary = f'----WebKitFormBoundary{uuid.uuid4().hex}'
    
    body = []
    body.append(f'--{boundary}'.encode('utf-8'))
    body.append(f'Content-Disposition: form-data; name="files[]"; filename="{filename}"'.encode('utf-8'))
    body.append(b'Content-Type: image/jpeg')
    body.append(b'')
    body.append(file_bytes)
    body.append(f'--{boundary}--'.encode('utf-8'))
    body.append(b'')
    
    payload = b'\r\n'.join(body)
    
    headers = {
        'Content-Type': f'multipart/form-data; boundary={boundary}',
        'Accept': 'application/json',
        'X-Livewire': 'true',
    }
    if csrf_token:
        headers['X-CSRF-TOKEN'] = csrf_token
        headers['X-XSRF-TOKEN'] = csrf_token

    req = urllib.request.Request(url, data=payload, headers=headers, method='POST')
    
    log_before = get_log_lines()
    
    try:
        with opener.open(req, timeout=45) as resp:
            status = resp.status
            response_text = resp.read().decode('utf-8', errors='ignore')
    except urllib.error.HTTPError as e:
        status = e.code
        response_text = e.read().decode('utf-8', errors='ignore')
    except Exception as e:
        status = 0
        response_text = str(e)

    log_after = get_log_lines()
    new_logs = log_after[len(log_before):]

    return status, response_text, new_logs

def main():
    base_url = 'http://127.0.0.1:8000/'
    upload_url = 'http://127.0.0.1:8000/livewire/upload-file'
    
    print("Fetching CSRF session token...")
    opener, cj, csrf_token = get_csrf_and_cookies(base_url)
    print(f"CSRF Token obtained: {'YES' if csrf_token else 'NO'}")

    test_cases = [
        ('10 KB', 10 * 1024),
        ('100 KB', 100 * 1024),
        ('1 MB', 1 * 1024 * 1024),
        ('5 MB', 5 * 1024 * 1024),
        ('10 MB', 10 * 1024 * 1024),
        ('20 MB', 20 * 1024 * 1024),
        ('50 MB', 50 * 1024 * 1024),
    ]

    print("\n==========================================================================")
    print("EMPIRICAL LIVEWIRE HTTP UPLOAD TEST ACROSS 7 EXACT FILE SIZES (WITH CSRF)")
    print("==========================================================================")

    failed_first_label = None

    for label, byte_size in test_cases:
        filename = f"test_{label.replace(' ', '_').lower()}.jpg"
        file_content = create_dummy_jpeg(byte_size)
        
        status, response_text, new_logs = test_upload(opener, upload_url, csrf_token, file_content, filename)
        
        is_success = (status == 200) and ('paths' in response_text or 'snapshot' in response_text or 'url' in response_text)
        
        if not is_success and failed_first_label is None:
            failed_first_label = (label, status, response_text, new_logs)

        print(f"\n--- FILE SIZE: {label} ({byte_size:,} bytes) ---")
        print(f"HTTP Status Code : {status}")
        print(f"Response Body    : {response_text[:300] if len(response_text) > 300 else response_text}")
        if new_logs:
            print(f"New Laravel Logs : {' '.join(new_logs)[:300]}")
        else:
            print("New Laravel Logs : None")
        print(f"Upload Result    : {'SUCCESS (200 OK)' if is_success else 'FAILED'}")

    print("\n==========================================================================")
    if failed_first_label:
        label, status, resp, logs = failed_first_label
        print(f"RESULT: FIRST FAILED FILE SIZE = {label}")
        print(f"HTTP Status Code = {status}")
        print(f"Response snippet = {resp[:400]}")
    else:
        print("ALL FILE SIZES PASSED UP TO 50 MB!")
    print("==========================================================================")

if __name__ == '__main__':
    main()
