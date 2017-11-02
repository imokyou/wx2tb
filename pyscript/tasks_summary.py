# coding=utf8
from gevent import monkey;monkey.patch_all()
from gevent.pool import Pool
import traceback
import json
import requests
from datetime import datetime
from settings import *
from models import *
import wxtoken


_db_url = 'mysql+mysqldb://%s:%s@%s/%s?charset=utf8mb4' % \
    (DATABASE['user'],
     DATABASE['passwd'],
     DATABASE['host'],
     DATABASE['db_name'])
_mgr = Mgr(create_engine(_db_url, pool_recycle=10))


def send_custom_text(account):
    wx_access_token = wxtoken.get_token()

    touser = account['account'].encode('utf8')
    currtime = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    text = '报客官, 截止 {} 您转换的短链接点击次数合计已达到整整 {} 次!!!'.format(currtime, account['clicks'])

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


def main():
    accounts = _mgr.get_account_summary()
    pools = Pool(10)
    pools.map(send_custom_text, accounts)


if __name__ == '__main__':
    main()
