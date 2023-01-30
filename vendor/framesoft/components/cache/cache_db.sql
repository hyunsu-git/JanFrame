DROP TABLE IF EXISTS `cache_20` ;

CREATE TABLE IF NOT EXISTS `cache_20` (
  `id` VARCHAR(45) NOT NULL,
  `value` TEXT NULL COMMENT '存放缓存值,根据业务改变类型',
  `create_time` BIGINT NULL,
  `expire_time` BIGINT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
COMMENT = '缓存组件使用的数据表';

CREATE INDEX `expire_index` ON `cache_20` (`expire_time` ASC);
