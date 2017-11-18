# coding=utf8
import requests
import wxtoken
from settings import *

def run():
    wx_access_token = wxtoken.get_token()
    api = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token='+wx_access_token['access_token']
    resp = requests.post(api, {})
    print resp
    if resp and resp.status_code == 200:
        content = resp.json()
        print content
        if content['errcode'] == 0:
            logging.info('获取素材列表成功！')
        else:
            logging.info('获取素材列表失败')
    else:
        logging.info('获取素材列表失败')

if __name__ == '__main__':
    run()
