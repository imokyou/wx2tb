CREATE DATABASE `wx2tb` DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `material` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(1024) NOT NULL COMMENT '物料名称',
    `content` VARCHAR(1024) NOT NULL DEFAULT '物料描述',
    `mid` VARCHAR(64) NOT NULL COMMENT '物料ID',
    `origin_url` VARCHAR(512) NOT NULL COMMENT '淘宝链接',
    `origin_url_md5` VARCHAR(128) NOT NULL COMMENT '淘宝链接排重',
    `code` VARCHAR(1024) NOT NULL DEFAULT '' COMMENT '淘口令', 
    `code_md5` VARCHAR(1024) NOT NULL DEFAULT '' COMMENT '淘口令排重', 
    `local_url` VARCHAR(512) NOT NULL DEFAULT '' COMMENT '本地链接，用于中间跳转',
    `short_url` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '短链接',
    `account` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '转换人ID',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `click_count` INT(11) NOT NULL DEFAULT '0' COMMENT '点击次数',
    `ext` TEXT COMMENT '扩展记录',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `convert_times` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `account` VARCHAR(1024) NOT NULL COMMENT '转换人ID',
    `date` DATE COMMENT '日期',
    `times` INT(11) COMMENT '次数',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;