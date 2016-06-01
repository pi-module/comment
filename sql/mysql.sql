# Pi Engine schema
# http://pialog.org
# Author: Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
# --------------------------------------------------------

# ------------------------------------------------------
# Comment
# >>>>

# Comment type
CREATE TABLE `{type}` (
  `id`         INT(10) UNSIGNED    NOT NULL    AUTO_INCREMENT,
  `module`     VARCHAR(64)         NOT NULL    DEFAULT '',
  `controller` VARCHAR(64)         NOT NULL    DEFAULT '',
  `action`     VARCHAR(64)         NOT NULL    DEFAULT '',
  `identifier` VARCHAR(64)         NOT NULL    DEFAULT '',
  `params`     VARCHAR(255)        NOT NULL    DEFAULT '',
  `name`       VARCHAR(64)         NOT NULL    DEFAULT '',
  `title`      VARCHAR(255)        NOT NULL    DEFAULT '',
  -- Callback to fetch source meta data
  `callback`   VARCHAR(255)        NOT NULL    DEFAULT '',
  -- Locator to identify root meta data
  `locator`    VARCHAR(255)        NOT NULL    DEFAULT '',
  `active`     TINYINT(1) UNSIGNED NOT NULL    DEFAULT '1',
  `icon`       VARCHAR(255)        NOT NULL    DEFAULT '',

  PRIMARY KEY (`id`),
  UNIQUE KEY `module_type` (`module`, `name`)
);

# Comment root
CREATE TABLE `{root}` (
  `id`     INT(10) UNSIGNED    NOT NULL    AUTO_INCREMENT,
  `module` VARCHAR(64)         NOT NULL,
  `type`   VARCHAR(64)         NOT NULL    DEFAULT '',
  `item`   INT(10) UNSIGNED    NOT NULL,
  `active` TINYINT(1) UNSIGNED NOT NULL    DEFAULT '1',
  -- User id of root item author
  `author` INT(10) UNSIGNED    NOT NULL    DEFAULT '0',

  PRIMARY KEY (`id`),
  UNIQUE KEY `module_item` (`module`, `type`, `item`),
  KEY `author` (`author`)
);

# Comment posts
CREATE TABLE `{post}` (
  `id`           INT(10) UNSIGNED    NOT NULL    AUTO_INCREMENT,
  `uid`          INT(10) UNSIGNED    NOT NULL    DEFAULT '0',
  `identity`     VARCHAR(64)         NOT NULL    DEFAULT '',
  `email`        VARCHAR(64)         NOT NULL    DEFAULT '',
  `root`         INT(10) UNSIGNED    NOT NULL,
  `reply`        INT(10) UNSIGNED    NOT NULL    DEFAULT '0',
  `content`      TEXT,
  -- Content markup: text, html, markdown
  `markup`       VARCHAR(64)         NOT NULL    DEFAULT '',
  `time`         INT(10) UNSIGNED    NOT NULL    DEFAULT '0',
  `time_updated` INT(10) UNSIGNED    NOT NULL    DEFAULT '0',
  `active`       TINYINT(1) UNSIGNED NOT NULL    DEFAULT '1',
  `ip`           VARCHAR(15)         NOT NULL    DEFAULT '',
  `module`       VARCHAR(64)         NOT NULL,

  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `root` (`root`)
);
