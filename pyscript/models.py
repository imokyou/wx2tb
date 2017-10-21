#!/usr/bin/env python
# encoding: utf-8
# 共用表定义
from sqlalchemy import *
from sqlalchemy import create_engine, Column, ForeignKey, String, Integer, Numeric, DateTime, Boolean, and_, or_, func
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker, relationship, backref
from sqlalchemy.dialects.mysql import VARCHAR, BIGINT, TINYINT, DATETIME, TEXT, CHAR
from settings import *

Base = declarative_base()


class BaseModel(Base):

    __abstract__ = True
    __table_args__ = {
        'extend_existing': True,
        'mysql_engine': 'InnoDB',
        'mysql_charset': 'utf8',
    }


class Material(BaseModel):

    __tablename__ = 'material'

    id = Column(Integer, primary_key=True)
    title = Column(VARCHAR(1024))
    mid = Column(VARCHAR(64))
    origin_url = Column(VARCHAR(512))
    origin_url_md5 = Column(VARCHAR(128))
    code = Column(VARCHAR(1024))
    code_md5 = Column(VARCHAR(1024))
    local_url = Column(VARCHAR(512))
    short_url = Column(VARCHAR(64))
    account = Column(VARCHAR(64))
    create_time = Column(Integer)
    update_time = Column(Integer)
    click_count = Column(Integer)
    content = Column(VARCHAR(1024))
    ext = Column(TEXT)

    def conv_result(self):
        ret = {}

        ret["id"] = self.id
        ret["title"] = self.title
        ret["mid"] = self.mid
        ret["origin_url"] = self.origin_url
        ret["origin_url_md5"] = self.origin_url_md5
        ret["code"] = self.code
        ret["code_md5"] = self.code_md5
        ret["local_url"] = self.local_url
        ret["short_url"] = self.short_url
        ret["account"] = self.account
        ret["create_time"] = self.create_time
        ret["update_time"] = self.update_time
        ret["click_count"] = self.click_count
        ret["content"] = self.content
        ret["ext"] = self.ext

        return ret


class MaterialImg(BaseModel):

    __tablename__ = 'material_img'

    id = Column(Integer, primary_key=True)
    material_id = Column(Integer)
    media_id = Column(VARCHAR(64))
    url = Column(VARCHAR(512))
    url_md5 = Column(VARCHAR(64))
    create_time = Column(Integer)
    update_time = Column(Integer)

    def conv_result(self):
        ret = {}

        ret["id"] = self.id
        ret["material_id"] = self.material_id
        ret["media_id"] = self.media_id
        ret["url"] = self.url
        ret["url_md5"] = self.url_md5
        ret["create_time"] = self.create_time
        ret["update_time"] = self.update_time
        return ret


class UserTasks(BaseModel):

    __tablename__ = "user_tasks"

    id = Column(Integer, primary_key=True)
    material_id = Column(Integer)
    account = Column(VARCHAR(64))
    is_sended = Column(Integer)

    def conv_result(self):
        ret = {}

        ret["id"] = self.id
        ret["material_id"] = self.material_id
        ret["account"] = self.account
        ret["is_sended"] = self.is_sended

        return ret


class Mgr(object):

    def __init__(self, engine):
        BaseModel.metadata.create_all(engine)
        self.session = sessionmaker(bind=engine)()
        self.engine = engine

    def get_user_tasks(self, params):
        try:
            ret = []
            q = self.session.query(UserTasks)
            if params.get('is_sended', '') != '':
                q = q.filter(UserTasks.is_sended == int(params['is_sended']))
            rows = q.all()
            for row in rows:
                ret.append(row.conv_result())
        except Exception as e:
            logging.warning("get user tasks error : %s" % e, exc_info=True)
        finally:
            self.session.close()
        return ret

    def finish_task(self, task_ids):
        try:
            self.session.query(UserTasks) \
                .filter(UserTasks.id.in_(task_ids)) \
                .update({'is_sended': 1}, synchronize_session='fetch')
            self.session.commit()
        except Exception as e:
            self.session.rollback()
            logging.warning("finish tasks error : %s" % e, exc_info=True)
        finally:
            self.session.close()

    def get_materials(self, params):
        try:
            ret = []
            q = self.session.query(Material)
            if params.get('material_id', '') != '':
                q = q.filter(Material.id == int(params['material_id']))
            rows = q.all()
            for row in rows:
                ret.append(row.conv_result())
        except Exception as e:
            logging.warning("get materials error : %s" % e, exc_info=True)
        finally:
            self.session.close()
        return ret

    def get_material_by_id(self, material_id):
        try:
            ret = {}
            rows = self.get_materials({'material_id': material_id})
            if rows:
                ret = rows[0]
        except Exception as e:
            logging.warning("get material error : %s" % e, exc_info=True)
        finally:
            self.session.close()
        return ret

    def material_update(self, material_id, data):
        try:
            self.session.query(Material) \
                .filter(Material.id == material_id) \
                .update(data)
            self.session.commit()
        except Exception as e:
            self.session.rollback()
            logging.warning("material update error : %s" % e, exc_info=True)
        finally:
            self.session.close()

    def add_material_img(self, info):
        try:
            self.session.add(MaterialImg(**info))
            self.session.commit()
        except Exception, e:
            self.session.rollback()
            logging.warning('add material img error: %s' % e, exc_info=True)
        finally:
            self.session.close()

    def get_material_img(self, params):
        try:
            ret = []
            q = self.session.query(MaterialImg)
            if params.get('url_md5', '') != '':
                q = q.filter(MaterialImg.url_md5 == params['url_md5'])
            rows = q.all()
            for row in rows:
                ret.append(row.conv_result())
        except Exception as e:
            logging.warning("get material img error : %s" % e, exc_info=True)
        finally:
            self.session.close()
        return ret
