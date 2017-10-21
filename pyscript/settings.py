# coding=utf8
import logging

logging.basicConfig(level=logging.INFO, format='%(asctime)s %(name)s:%(levelname)s %(message)s')

# 协程池大小
WORKER_THREAD_NUM = 100

# 数据库设计
DATABASE = {
    'host': '39.108.53.90',
    'user': 'root',
    'passwd': 'lupin2008cn',
    'port': 3306,
    'db_name': 'wx2tb'
}

WX = {
    'token_api': 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wxb1197b6e59b79e6d&secret=1e1a1d683643f63cf23a98be2b1ed72c',
    'msg_api': 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=',
    'media_api': 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='
}


TKL = {
    'accounts': [
        {'u': 'imokyou', 'p': '7457267293'},
        {'u': '18664783659', 'p': '7457267293'}
    ],
    'api': 'http://www.taokouling.com/index.php?m=api&a=taokoulingjm',
    'domains': ['http://tb.js101.local']
}
SHORT_URL = {
    'key': 'kJyVQncjui',
    'secret': 'BFLXqtbibRkedWTnPNOpWFHQpUPNitWX',
    'api': [
        'https://0x3.me/apis/authorize/getCode',
        'https://0x3.me/apis/authorize/getAccessToken'
    ],
    'service_api': {
        'add': 'https://0x3.me/apis/urls/add',
        'modify': 'https://0x3.me/apis/url/modify'
    }
}
