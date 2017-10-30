# coding=utf8
import traceback
import requests
import hashlib
import json
from datetime import datetime
from bs4 import BeautifulSoup
from settings import *


_common_data = {
    'method': '',
    'app_key': TPWD['appkey'],
    'sign_method': 'md5',
    'timestamp': '',
    'format': 'json',
    'v': '2.0'
}


def get_sign(secret, parameters):
    if hasattr(parameters, "items"):
        keys = parameters.keys()
        keys.sort()

        parameters = "%s%s%s" % (secret,
            str().join('%s%s' % (key, parameters[key]) for key in keys),
            secret)
    sign = hashlib.md5(parameters).hexdigest().upper()
    return sign


def is_valid_url(url):
    pass


def get_item_byhttp(url):
    ret = {}
    try:
        resp = requests.get(url)
        if resp and resp.status_code == 200:
            soup = BeautifulSoup(resp.content, 'lxml')
            img = soup.find("img", id="J_ImgBooth")

            ret = {
                'content': soup.title.text,
                'pic_url': ''
            }
            try:
                ret['pic_url'] = 'http:'+img.attrs.get('src')
            except:
                traceback.print_exc()
    except:
        traceback.print_exc()
    return ret


def set_param(url):
    ret = {
        'url': url,
        'text': '淘宝购买链接',
    }
    item = get_item_byhttp(url)

    if item:
        ret['text'] = item['content']
    if item.get('pic_url', ''):
        ret['logo'] = item['pic_url']
    return ret


def get_page(api, data, retry=0):
    try:
        if retry >= 4:
            return None
        resp = requests.post(TPWD['api'], data, timeout=10)
        if resp.status_code != 200:
            retry += 1
            get_page(api, data, retry)
        return resp.json()
    except:
        retry += 1
        get_page(api, data, retry)


def get_tpwd(url):
    ret = {}
    tpwd_param = set_param(url)

    data = _common_data
    data['method'] = 'taobao.wireless.share.tpwd.create'
    data['timestamp'] = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    data['tpwd_param'] = json.dumps(tpwd_param)
    sign = get_sign(TPWD['appsecret'], data)
    data['sign'] = sign

    try:
        content = get_page(TPWD['api'], data)
        ret = {'code': content['wireless_share_tpwd_create_response']['model']}
    except:
        traceback.print_exc()
    return ret

if __name__ == '__main__':
    # get_tpwd('https://www.taobao.com/markets/tbhome/crowd-guide?spm=a21bo.2017.201868.6.80e38db0UT58I&id=930&itemId=528585399004&pvid=9aa1a92c-7fa1-4cc9-b9b3-aa220a6d144e&scm=1007.12952.88560.100200300000000')
    ret = get_tpwd('https://item.taobao.com/item.htm?spm=2013.1.20141001.2.493f7caeZzVNAx&id=558959135364&scm=1007.12144.81309.42296_42296&pvid=9d2baedf-dee0-451e-964b-ba7be3489f5c')
    if ret:
        print ret
        print ret['code']
    else:
        print 'Nothing'
    # get_item_byhttp('https://item.taobao.com/item.htm?spm=a230r.1.14.30.77a2477ec4JOnS&id=558294052678&ns=1&abbucket=2#detail')
