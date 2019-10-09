CREATE TABLE `babel_mobile_data` (
  `mob_no` int(10) unsigned NOT NULL,
  `mob_area` varchar(20) NOT NULL,
  `mob_subarea` varchar(20) NOT NULL,
  PRIMARY KEY (`mob_no`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Babel Mobile Data Table';
ALTER TABLE `babel_online` TYPE=HEAP, COMMENT='Babel Online Table', ROW_FORMAT=FIXED;
CREATE TABLE `babel_savepoint` (
  `svp_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `svp_uid` int(10) unsigned NOT NULL DEFAULT '0',
  `svp_url` varchar(400) NOT NULL DEFAULT '',
  `svp_rank` int(10) unsigned NOT NULL DEFAULT '0',
  `svp_created` int(10) unsigned NOT NULL DEFAULT '0',
  `svp_lastupdated` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`svp_id`),
  KEY `INDEX_UID` (`svp_uid`),
  KEY `INDEX_URL` (`svp_url`(333))
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Babel Savepoint Table';
CREATE TABLE `babel_zen_project` (
  `zpr_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `zpr_uid` int(10) unsigned NOT NULL DEFAULT '0',
  `zpr_private` int(10) unsigned NOT NULL DEFAULT '0',
  `zpr_title` varchar(100) NOT NULL,
  `zpr_progress` int(10) unsigned NOT NULL DEFAULT '0',
  `zpr_created` int(10) unsigned NOT NULL DEFAULT '0',
  `zpr_lastupdated` int(10) unsigned NOT NULL DEFAULT '0',
  `zpr_lasttouched` int(10) unsigned NOT NULL DEFAULT '0',
  `zpr_completed` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`zpr_id`),
  KEY `INDEX_UID` (`zpr_uid`),
  KEY `INDEX_PRIVATE` (`zpr_private`),
  KEY `INDEX_PROGRESS` (`zpr_progress`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Babel ZEN Project Table';
CREATE TABLE `babel_zen_task` (
  `zta_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `zta_uid` int(10) unsigned NOT NULL DEFAULT '0',
  `zta_pid` int(10) unsigned NOT NULL DEFAULT '0',
  `zta_title` varchar(100) NOT NULL,
  `zta_progress` int(10) unsigned NOT NULL DEFAULT '0',
  `zta_created` int(10) unsigned NOT NULL DEFAULT '0',
  `zta_lastupdated` int(10) unsigned NOT NULL DEFAULT '0',
  `zta_completed` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`zta_id`),
  KEY `INDEX_UID` (`zta_uid`),
  KEY `INDEX_PID` (`zta_pid`),
  KEY `INDEX_PROGRESS` (`zta_progress`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Babel ZEN Task Table';
ALTER TABLE `babel_node` ADD COLUMN `nod_weight` INTEGER(11) NOT NULL DEFAULT '0';
ALTER TABLE `babel_online` MODIFY COLUMN `onl_ip` VARCHAR(15) COLLATE utf8_general_ci DEFAULT '0.0.0.0' UNIQUE;
ALTER TABLE `babel_user` ADD COLUMN `usr_width` INTEGER(10) UNSIGNED NOT NULL DEFAULT '1024';
ALTER TABLE `babel_user` MODIFY COLUMN `usr_hits` INTEGER(10) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `babel_online` ADD KEY `INDEX_IP` (`onl_ip`);
