-- MySQL dump 10.10
--
-- Host: localhost    Database: planescape
-- ------------------------------------------------------
-- Server version	5.1.11-beta

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `babel_channel`
--

DROP TABLE IF EXISTS `babel_channel`;
CREATE TABLE `babel_channel` (
  `chl_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chl_pid` int(10) unsigned NOT NULL DEFAULT '0',
  `chl_title` varchar(200) NOT NULL DEFAULT '',
  `chl_url` varchar(200) NOT NULL DEFAULT '',
  `chl_created` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`chl_id`),
  KEY `INDEX_PID` (`chl_pid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 COMMENT='Babel Channel Table';

--
-- Table structure for table `babel_expense`
--

DROP TABLE IF EXISTS `babel_expense`;
CREATE TABLE `babel_expense` (
  `exp_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `exp_uid` int(10) unsigned NOT NULL DEFAULT '0',
  `exp_amount` int(11) NOT NULL DEFAULT '0',
  `exp_type` int(10) unsigned NOT NULL DEFAULT '0',
  `exp_memo` text,
  `exp_created` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`exp_id`),
  KEY `INDEX_UID` (`exp_uid`),
  KEY `INDEX_TYPE` (`exp_type`),
  KEY `INDEX_CREATED` (`exp_created`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Babel Expense Table';

--
-- Table structure for table `babel_favorite`
--

DROP TABLE IF EXISTS `babel_favorite`;
CREATE TABLE `babel_favorite` (
  `fav_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fav_uid` int(10) unsigned NOT NULL DEFAULT '0',
  `fav_title` varchar(200) NOT NULL DEFAULT '',
  `fav_author` varchar(100) NOT NULL DEFAULT '',
  `fav_res` varchar(200) NOT NULL DEFAULT '',
  `fav_brief` text,
  `fav_type` int(10) unsigned NOT NULL DEFAULT '0',
  `fav_created` int(10) unsigned NOT NULL DEFAULT '0',
  `fav_lastupdated` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`fav_id`),
  KEY `INDEX_UID` (`fav_uid`),
  KEY `INDEX_RES` (`fav_res`),
  KEY `INDEX_TYPE` (`fav_type`),
  KEY `INDEX_CREATED` (`fav_created`),
  KEY `INDEX_LASTUPDATED` (`fav_lastupdated`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Babel Favorite Table';

--
-- Table structure for table `babel_foundation`
--

DROP TABLE IF EXISTS `babel_foundation`;
CREATE TABLE `babel_foundation` (
  `fdt_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fdt_uid` int(10) unsigned NOT NULL DEFAULT '0',
  `fdt_title` varchar(40) NOT NULL DEFAULT 'Untitled foundation',
  `fdt_money` int(11) NOT NULL DEFAULT '0',
  `fdt_type` int(10) unsigned NOT NULL DEFAULT '0',
  `fdt_brief` text,
  `fdt_created` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`fdt_id`),
  KEY `INDEX_UID` (`fdt_uid`),
  KEY `INDEX_TYPE` (`fdt_type`),
  KEY `INDEX_CREATED` (`fdt_created`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Babel Foundation Table';

--
-- Table structure for table `babel_friend`
--

DROP TABLE IF EXISTS `babel_friend`;
CREATE TABLE `babel_friend` (
  `frd_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `frd_uid` int(10) unsigned NOT NULL DEFAULT '0',
  `frd_fid` int(10) unsigned NOT NULL DEFAULT '0',
  `frd_description` varchar(200) NOT NULL DEFAULT '',
  `frd_created` int(10) unsigned NOT NULL DEFAULT '0',
  `frd_lastupdated` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`frd_id`),
  KEY `INDEX_UID` (`frd_uid`),
  KEY `INDEX_FID` (`frd_fid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=0 COMMENT='Babel Friend Table';

--
-- Table structure for table `babel_group`
--

DROP TABLE IF EXISTS `babel_group`;
CREATE TABLE `babel_group` (
  `grp_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `grp_oid` int(10) unsigned NOT NULL DEFAULT '0',
  `grp_nick` varchar(40) NOT NULL DEFAULT '',
  `grp_brief` longtext,
  `grp_created` int(10) unsigned NOT NULL DEFAULT '0',
  `grp_lastupdated` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`grp_id`),
  KEY `INDEX_OID` (`grp_oid`),
  KEY `INDEX_NICK` (`grp_nick`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Babel Group Table';

--
-- Table structure for table `babel_message`
--

DROP TABLE IF EXISTS `babel_message`;
CREATE TABLE `babel_message` (
  `msg_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `msg_sid` int(10) unsigned NOT NULL DEFAULT '0',
  `msg_rid` int(10) unsigned NOT NULL DEFAULT '0',
  `msg_body` text,
  `msg_draft` int(10) unsigned NOT NULL DEFAULT '0',
  `msg_hits` int(10) unsigned NOT NULL DEFAULT '0',
  `msg_created` int(10) unsigned NOT NULL DEFAULT '0',
  `msg_sent` int(10) unsigned NOT NULL DEFAULT '0',
  `msg_opened` int(10) unsigned NOT NULL DEFAULT '0',
  `msg_sdeleted` int(10) unsigned NOT NULL DEFAULT '0',
  `msg_rdeleted` int(10) unsigned NOT NULL DEFAULT '0',
  `msg_lastaccessed` int(10) unsigned NOT NULL DEFAULT '0',
  `msg_lastupdated` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`msg_id`),
  KEY `INDEX_SID` (`msg_sid`),
  KEY `INDEX_RID` (`msg_rid`),
  KEY `INDEX_DRAFT` (`msg_draft`),
  KEY `INDEX_CREATED` (`msg_created`),
  KEY `INDEX_SENT` (`msg_sent`),
  KEY `INDEX_SDELETED` (`msg_sdeleted`),
  KEY `INDEX_RDELETED` (`msg_rdeleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Babel Message Table';

--
-- Table structure for table `babel_mobile_data`
--

DROP TABLE IF EXISTS `babel_mobile_data`;
CREATE TABLE `babel_mobile_data` (
  `mob_no` int(10) unsigned NOT NULL,
  `mob_area` varchar(20) NOT NULL,
  `mob_subarea` varchar(20) NOT NULL,
  PRIMARY KEY (`mob_no`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Babel Mobile Data Table';

--
-- Table structure for table `babel_node`
--

DROP TABLE IF EXISTS `babel_node`;
CREATE TABLE `babel_node` (
  `nod_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nod_pid` int(10) unsigned NOT NULL DEFAULT '5',
  `nod_uid` int(10) unsigned NOT NULL DEFAULT '1',
  `nod_sid` int(10) unsigned NOT NULL DEFAULT '5',
  `nod_level` int(10) unsigned NOT NULL DEFAULT '2',
  `nod_name` varchar(100) NOT NULL DEFAULT 'node',
  `nod_title` varchar(100) NOT NULL DEFAULT 'Untitled node',
  `nod_description` text,
  `nod_header` text,
  `nod_footer` text,
  `nod_portrait` varchar(40) DEFAULT NULL,
  `nod_topics` int(10) unsigned NOT NULL DEFAULT '0',
  `nod_favs` int(10) unsigned NOT NULL DEFAULT '0',
  `nod_weight` int(11) NOT NULL DEFAULT '0',
  `nod_created` int(10) unsigned NOT NULL DEFAULT '0',
  `nod_lastupdated` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`nod_id`),
  KEY `INDEX_PID` (`nod_pid`),
  KEY `INDEX_UID` (`nod_uid`),
  KEY `INDEX_SID` (`nod_sid`),
  KEY `INDEX_TOPICS` (`nod_topics`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Babel Node Table';

--
-- Table structure for table `babel_online`
--

DROP TABLE IF EXISTS `babel_online`;
CREATE TABLE `babel_online` (
  `onl_hash` char(32) NOT NULL DEFAULT '',
  `onl_nick` varchar(40) DEFAULT NULL,
  `onl_ua` varchar(200) DEFAULT NULL,
  `onl_ip` varchar(15) DEFAULT '0.0.0.0',
  `onl_uri` varchar(200) DEFAULT '/',
  `onl_ref` varchar(200) DEFAULT '/',
  `onl_created` int(10) unsigned NOT NULL DEFAULT '0',
  `onl_lastmoved` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`onl_hash`),
  KEY `INDEX_NICK` (`onl_nick`),
  KEY `INDEX_CREATED` (`onl_created`),
  KEY `INDEX_LASTMOVED` (`onl_lastmoved`),
  KEY `INDEX_IP` (`onl_ip`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8 COMMENT='Babel Online Table';

--
-- Table structure for table `babel_passwd`
--

DROP TABLE IF EXISTS `babel_passwd`;
CREATE TABLE `babel_passwd` (
  `pwd_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pwd_uid` int(10) unsigned NOT NULL DEFAULT '0',
  `pwd_hash` char(100) DEFAULT NULL,
  `pwd_ip` varchar(15) NOT NULL DEFAULT '0.0.0.0',
  `pwd_created` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pwd_id`),
  KEY `INDEX_UID` (`pwd_uid`),
  KEY `INDEX_HASH` (`pwd_hash`),
  KEY `INDEX_CREATED` (`pwd_created`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Babel Passwd Table';

--
-- Table structure for table `babel_post`
--

DROP TABLE IF EXISTS `babel_post`;
CREATE TABLE `babel_post` (
  `pst_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pst_tid` int(10) unsigned NOT NULL DEFAULT '5',
  `pst_uid` int(10) unsigned NOT NULL DEFAULT '0',
  `pst_title` varchar(100) DEFAULT 'Untitled reply',
  `pst_content` text,
  `pst_created` int(10) unsigned NOT NULL DEFAULT '0',
  `pst_lastupdated` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pst_id`),
  KEY `INDEX_TID` (`pst_tid`),
  KEY `INDEX_UID` (`pst_uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Babel Post Table';

--
-- Table structure for table `babel_savepoint`
--

DROP TABLE IF EXISTS `babel_savepoint`;
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

--
-- Table structure for table `babel_surprise`
--

DROP TABLE IF EXISTS `babel_surprise`;
CREATE TABLE `babel_surprise` (
  `srp_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `srp_uid` int(10) unsigned NOT NULL DEFAULT '0',
  `srp_amount` int(11) NOT NULL DEFAULT '0',
  `srp_type` int(10) unsigned NOT NULL DEFAULT '0',
  `srp_memo` text,
  `srp_created` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`srp_id`),
  KEY `INDEX_UID` (`srp_uid`),
  KEY `INDEX_TYPE` (`srp_type`),
  KEY `INDEX_CREATED` (`srp_created`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Babel Surprise Table';

--
-- Table structure for table `babel_topic`
--

DROP TABLE IF EXISTS `babel_topic`;
CREATE TABLE `babel_topic` (
  `tpc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tpc_pid` int(10) unsigned NOT NULL DEFAULT '5',
  `tpc_uid` int(10) unsigned NOT NULL DEFAULT '0',
  `tpc_title` varchar(100) NOT NULL DEFAULT 'Untitled topic',
  `tpc_description` text,
  `tpc_content` text,
  `tpc_hits` int(10) unsigned NOT NULL DEFAULT '0',
  `tpc_refs` int(10) unsigned NOT NULL DEFAULT '0',
  `tpc_posts` int(10) unsigned NOT NULL DEFAULT '0',
  `tpc_flag` int(10) unsigned NOT NULL DEFAULT '0',
  `tpc_created` int(10) unsigned NOT NULL DEFAULT '0',
  `tpc_lastupdated` int(10) unsigned NOT NULL DEFAULT '0',
  `tpc_lasttouched` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tpc_id`),
  KEY `INDEX_PID` (`tpc_pid`),
  KEY `INDEX_UID` (`tpc_uid`),
  KEY `INDEX_POSTS` (`tpc_posts`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Babel Topic Table';

--
-- Table structure for table `babel_user`
--

DROP TABLE IF EXISTS `babel_user`;
CREATE TABLE `babel_user` (
  `usr_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usr_gid` int(10) unsigned NOT NULL DEFAULT '0',
  `usr_nick` varchar(40) NOT NULL DEFAULT '',
  `usr_password` varchar(40) NOT NULL DEFAULT '',
  `usr_email` varchar(100) DEFAULT NULL,
  `usr_full` varchar(40) DEFAULT NULL,
  `usr_addr` varchar(200) DEFAULT NULL,
  `usr_telephone` varchar(40) DEFAULT NULL,
  `usr_identity` varchar(18) DEFAULT NULL,
  `usr_gender` smallint(6) NOT NULL DEFAULT '0',
  `usr_birthday` int(10) unsigned NOT NULL DEFAULT '0',
  `usr_portrait` varchar(40) DEFAULT NULL,
  `usr_brief` longtext,
  `usr_money` double NOT NULL DEFAULT '0',
  `usr_width` int(10) unsigned NOT NULL DEFAULT '1024',
  `usr_hits` int(10) unsigned NOT NULL DEFAULT '0',
  `usr_api` int(10) unsigned NOT NULL DEFAULT '0',
  `usr_editor` varchar(20) NOT NULL DEFAULT 'default',
  `usr_created` int(10) unsigned NOT NULL DEFAULT '0',
  `usr_lastupdated` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`usr_id`),
  KEY `INDEX_GID` (`usr_gid`),
  KEY `INDEX_NICK` (`usr_nick`),
  KEY `INDEX_PASSWORD` (`usr_password`),
  KEY `INDEX_EMAIL` (`usr_email`),
  KEY `INDEX_API` (`usr_api`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Babel User Table';

--
-- Table structure for table `babel_zen_project`
--

DROP TABLE IF EXISTS `babel_zen_project`;
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

--
-- Table structure for table `babel_zen_task`
--

DROP TABLE IF EXISTS `babel_zen_task`;
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

INSERT INTO `babel_node`(`nod_pid`, `nod_level`, `nod_name`, `nod_title`) VALUES(1, 0, 'planescape', '异域');

INSERT INTO `babel_node`(`nod_pid`, `nod_level`, `nod_name`, `nod_title`) VALUES(1, 1, 'limbo', '混沌海');

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;