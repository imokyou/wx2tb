# coding=utf8
import requests
import os
import json
import hashlib
from time import sleep, time
from settings import *

pwd = os.getcwd()
_filename = pwd + 'res/short_url_access_token.txt'


def md5(s):
    m = hashlib.md5()
    m.update(s)
    return m.hexdigest()


def get_sign(params):
    sign = ''
    unsign = ''
    sorted_data = [(k, params[k]) for k in sorted(params.keys())]
    for item in sorted_data:
        unsign += '%s=%s' % item
    sign = md5(unsign+SHORT_URL['secret'])
    return sign


def get_token():
    logging.info('管理短链接服务的access token...')
    if not os.path.isfile(_filename):
        with open(_filename, 'wb') as f:
            pass

    is_expired = False
    data = {}
    with open(_filename, 'rb') as f:
        try:
            data = json.loads(f.read())
            if data['expire_timestamp'] - int(time()) - 1000 < 0:
                is_expired = True
        except:
            data = {}
            is_expired = True

    if is_expired:
        resp = requests.get(SHORT_URL['api'][0])
        code = resp.json()

        params = {
            'code': code['data'],
            'api_key': SHORT_URL['key'],
            'request_time': int(time())
        }
        sign = get_sign(params)
        params['sign'] = sign
        resp = requests.post(SHORT_URL['api'][1], params)
        if resp and resp.status_code == 200:
            content = resp.json()
            if content['status'] == 1:
                data = content['data']
                logging.info('成功获取ACCESS TOKEN')

                with open(_filename, 'wb') as f:
                    f.write(json.dumps(data))
    else:
        logging.info('时间戳还在有效期内')
    return data


def main():
    retry_times = 0
    while True:
        token = get_token()
        if token and token['expire_timestamp'] - int(time()) - 1000 > 0:
            break
        sleep(10)
        retry_times += 1

if __name__ == '__main__':
    main()
