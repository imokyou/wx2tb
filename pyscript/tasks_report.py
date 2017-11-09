# coding=utf8
from gevent import monkey;monkey.patch_all()
from gevent.pool import Pool
import traceback
import json
import requests
from datetime import datetime, timedelta
from time import time
from settings import *
from models import *
import wxtoken


_db_url = 'mysql+mysqldb://%s:%s@%s/%s?charset=utf8mb4' % \
    (DATABASE['user'],
     DATABASE['passwd'],
     DATABASE['host'],
     DATABASE['db_name'])
_mgr = Mgr(create_engine(_db_url, pool_recycle=10))


def send_custom_text(t):
    wx_access_token = wxtoken.get_token()

    touser = t['account'].encode('utf8')
    cur_time = time()
    beg = int(cur_time - (cur_time + 8 * 3600) % 86400)
    report = _mgr.get_account_report(t['account'], beg)
    if not report:
        text = '{} 昨日\n转链接数: 0条\n总占击数: 0次\n最高点击链接: 无\n最高点击次数: 0次\n'.format(
            (datetime.now()-timedelta(days=1)).strftime('%m-%d')
        )
    else:
        link_num = len(report)
        total_clicks = 0
        top_link = ''
        top_link_click = 0
        for r in report:
            if r['clicks'] > top_link_click:
                top_link_click = r['clicks']
                top_link = r['short_url']
            total_clicks += r['clicks']

        text = '{} 昨日\n转链接数: {}条\n总占击数: {}次\n最高点击链接: {}\n最高点击次数: {}次'.format(
            (datetime.now()-timedelta(days=1)).strftime('%m-%d'),
            link_num,
            total_clicks,
            top_link,
            top_link_click
        )

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
    ts = _mgr.get_user_report_tasks({'is_sended': 0})
    pools = Pool(10)
    pools.map(send_custom_text, ts)


if __name__ == '__main__':
    main()
