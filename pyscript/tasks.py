# coding=utf8
from gevent import monkey;monkey.patch_all()
from gevent.pool import Pool
import hashlib
import requests
import urllib
from random import randint
import os
import json
from datetime import datetime
from time import time, sleep
from settings import *
from models import *
import shtoken
import wxtoken


_db_url = 'mysql+mysqldb://%s:%s@%s/%s?charset=utf8mb4' % \
    (DATABASE['user'],
     DATABASE['passwd'],
     DATABASE['host'],
     DATABASE['db_name'])
_mgr = Mgr(create_engine(_db_url, pool_recycle=10))


def md5(s):
    m = hashlib.md5()
    m.update(s)
    return m.hexdigest()


def get_tasks():
    ret = _mgr.get_user_tasks({'is_sended': 0})
    return ret


def decrynt_code(task):
    ret = {}

    material = _mgr.get_material_by_id(int(task['material_id']))
    if material['origin_url'] and material['content']:
        ret['mid'] = material['mid']
        ret['origin_url'] = material['origin_url']
        ret['origin_url_md5'] = material['origin_url_md5']
        ret['content'] = material['content']
        ret['local_url'] = material['local_url']
        ret['ext'] = material['ext']
        return ret

    index = randint(0, len(TKL['accounts'])-1)
    account = TKL['accounts'][index]

    params = {
        'username': account['u'],
        'password': account['p'],
        'text': material['code']
    }
    api = TKL['api']

    resp = requests.post(api, params)
    if resp and resp.status_code == 200:
        content = resp.json()
        ret['mid'] = content['taopwdOwnerId']
        ret['origin_url'] = content['url']
        ret['origin_url_md5'] = md5(content['url'])
        ret['content'] = content['content']
        ret['ext'] = resp.content

        domain = TKL['domains'][randint(0, len(TKL['domains'])-1)]
        ret['local_url'] = domain+'/go?itemid='+str(task['material_id'])
    return ret


def get_short_url(task, lurl):
    ret = {}

    material = _mgr.get_material_by_id(int(task['material_id']))
    if material['short_url']:
        ret['short_url'] = material['short_url']
        return ret

    access_token = shtoken.get_token()
    api = SHORT_URL['service_api']['add']
    params = {
        'access_token': access_token['access_token'],
        'longurl': lurl,
        'domain': '0x5'
    }
    resp = requests.post(api, params)
    if resp and resp.status_code == 200:
        content = resp.json()
        ret['short_url'] = content['data']['short_url']
    else:
        logging.info('转换短链接失败')
    return ret


def upload_img_to_wx(img_url):
    img_url_md5 = md5(img_url)
    media = _mgr.get_material_img({'url_md5': img_url_md5})
    print media
    if len(media):
        return media[0]['media_id']

    file_suffix = os.path.splitext(img_url)[1]
    pwd = os.path.dirname(os.path.realpath(__file__))
    filename = pwd + '/../runtime/temp/'+md5(img_url) + file_suffix
    if not os.path.isfile(filename):
        resp = requests.get(img_url)
        with open(filename, 'wb') as f:
            for chunk in resp:
                f.write(chunk)

    wx_access_token = wxtoken.get_token()
    params = {
        'access_token': wx_access_token['access_token'],
        'type': 'image'
    }
    api = WX['media_api'] + wx_access_token['access_token']
    resp = requests.post(api, params, files={'media': open(filename, 'rb')})
    if resp and resp.status_code == 200:
        content = resp.json()
        if content.get('media_id', '') != '':
            logging.info('图片上传成功')
            _mgr.add_material_img({
                'media_id': content['media_id'],
                'material_id': 0,
                'url': img_url,
                'url_md5': img_url_md5,
                'create_time': int(time()),
                'update_time': 0
            })
            return content['media_id']
        else:
            logging.info('图片上传失败'+content['errmsg'].encode('utf8'))
    else:
        logging.info('图片上传失败')
    return None


def send_custom_text(touser, text):
    wx_access_token = wxtoken.get_token()
    params = {
        "touser": touser,
        "msgtype": "text",
        "text": {
            "content": text
        }
    }
    api = WX['msg_api'] + wx_access_token['access_token']
    resp = requests.post(api, json.dumps(params, ensure_ascii=False))
    if resp and resp.status_code == 200:
        content = resp.json()
        if content['errcode'] == 0:
            logging.info('消息发送成功！')
        else:
            logging.info('消息发送失败'+content['errmsg'].encode('utf8'))
    else:
        logging.info('消息发送失败')


def send_custom_img(touser, img):
    media_id = upload_img_to_wx(img)

    wx_access_token = wxtoken.get_token()
    params = {
        "touser": touser.encode('utf8'),
        "msgtype": "image",
        "image": {
            "media_id": media_id
        }
    }
    api = WX['msg_api'] + wx_access_token['access_token']
    resp = requests.post(api, json.dumps(params, ensure_ascii=False))
    if resp and resp.status_code == 200:
        content = resp.json()
        if content['errcode'] == 0:
            logging.info('消息发送成功！')
        else:
            logging.info('消息发送失败'+content['errmsg'].encode('utf8'))
    else:
        logging.info('消息发送失败')


def send_msg(task):
    decrynt_data = decrynt_code(task)
    if not decrynt_data:
        return None

    short_data = get_short_url(task, decrynt_data['local_url'])
    data = dict(decrynt_data.items() + short_data.items())
    _mgr.material_update(task['material_id'], data)

    tcode = json.loads(data['ext'])
    text = '您要找的【 '+data['content'].encode('utf8')+' 】在这里~, 点击链接购买 '+data['short_url'].encode('utf8')

    send_custom_text(task['account'].encode('utf8'), text)
    send_custom_img(task['account'].encode('utf8'), tcode['picUrl'])

    _mgr.finish_task([task['id']])


def should_send():
    return True


def main():
    while True:
        tasks = get_tasks()
        if tasks:
            pools = Pool(WORKER_THREAD_NUM)
            pools.map(send_msg, tasks)
        else:
            logging.info('没有需要执行的任务')
        sleep(TASK_INTERVAL)


if __name__ == '__main__':
    main()
    # send_custom_text('o6WPn0zd-NcgCXwsDu2hHDP8MMwU', text)
    # send_custom_img('o6WPn0zd-NcgCXwsDu2hHDP8MMwU', 'http://gw.alicdn.com/bao/uploaded/i3/81397564/TB2OmPgx98mpuFjSZFMXXaxpVXa_!!81397564.png')
