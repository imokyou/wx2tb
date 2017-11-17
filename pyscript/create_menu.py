# coding=utf8
import json
import requests
import wxtoken
from settings import *


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
                    "url": "https://mp.weixin.qq.com/s?__biz=MzU1NTE1Njk2OQ==&tempkey=OTMxX2MzVHlsdjBnTlFkbFBGRC8tbElWRHJvbzhyNXhzTTRXaG1LSlhPbTRuRnhTYWlLMk1pYmt4SE8tOXVHYUwteVQ2cXpkRGdWeVZ3OUYxOExheTdkQUlQQTZ5eVR5cTJSOEZ2eVFhVlptZGRycnIxem95LVI3M2FndXh5aW9zdG1SaDljWURtTkdPMlF4eEdUTTIzaXBYYTVmdWc3X3psQ1BMSGVUS0F%2Bfg%3D%3D&chksm=7bd9d58e4cae5c985887c94ce0ef169636641d3f1c1a43764030f41a3e01548f62ad0009f0db#rd"
                },
                {
                    "type": "view",
                    "name": "数据统计功能",
                    "url": "https://mp.weixin.qq.com/s?__biz=MzU1NTE1Njk2OQ==&tempkey=OTMxX0Q5QjFiV09UcTdLZWtDbE0tbElWRHJvbzhyNXhzTTRXaG1LSlhPbTRuRnhTYWlLMk1pYmt4SE8tOXVGUU1GcHVCdEFfdnc0Z0QtOFBPa2JnRkVDclZkcXZtOFpPT0dTbzJXc1RTTEhDVnliQ3ZHRmIydUdpQzNLNkxNZ3ROeUpFM3VIZVhyTzRDSzBKNnFZeXV0RUVBQzR5WXVzSWtTZlAwNVZ1cWd%2Bfg%3D%3D&chksm=7bd9d5964cae5c80c4dc57b14ea9837c56fe8aafdaa43fa18a569a699cdeaf269d222d2df550#rd"
                }
            ]
        }, {
            "type": "media_id",
            "name": "客服",
            "media_id": "Xv9SYhJ_9-L_YHpVKQ64XqlNbfYdc9_kgeqqdfQ6AGbHCugxv8iEv4M-wryGVMqx",
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
