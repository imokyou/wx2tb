# coding=utf8
import json
import requests
import wxtoken
from settings import *

# {u'url': u'http://mmbiz.qpic.cn/mmbiz_png/qNVsD0e80f20mMibnibYDVfKDAn1Ux3vXiasiaKIda3TLhglc5gzsjamWkBZDqrhjLyFnV31YhUYg8svrKa7bm9mXQ/0?wx_fmt=png', u'media_id': u'ojgSex-sKR8g6frh3NRo72EiWFxHkvOPHAS9uE5TgVM'}


def run():
    API = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='
    wx_access_token = wxtoken.get_token()
    params = {
        "button": [{
            "name": "帮助",
            "sub_button": [
                {
                    "type": "view",
                    "name": "淘口令转短链接操作方法",
                    "url": "https://mp.weixin.qq.com/s/f5xKv1vjkV-aQ-bGvozZ2A"
                },
                {
                    "type": "view",
                    "name": "数据统计功能",
                    "url": "https://mp.weixin.qq.com/s/RoJqkHbW_KeXmdK2koeEEQ"
                }
            ]
        }, {
            "type": "media_id",
            "name": "客服",
            "media_id": "ojgSex-sKR8g6frh3NRo72EiWFxHkvOPHAS9uE5TgVM",
        }]
    }
    api = API + wx_access_token['access_token']
    resp = requests.post(api, json.dumps(params, ensure_ascii=False))
    if resp and resp.status_code == 200:
        content = resp.json()
        if content['errcode'] == 0:
            logging.info('菜单创建成功！')
        else:
            logging.info('菜单创建失败'+content['errmsg'].encode('utf8'))
    else:
        logging.info('菜单创建失败')


if __name__ == '__main__':
    run()
