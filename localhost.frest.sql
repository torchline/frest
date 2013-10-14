/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50612
Source Host           : localhost:3306
Source Database       : frest

Target Server Type    : MYSQL
Target Server Version : 50612
File Encoding         : 65001

Date: 2013-10-13 20:00:33
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for message
-- ----------------------------
DROP TABLE IF EXISTS `message`;
CREATE TABLE `message` (
`ID`  int(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
`SenderUserID`  bigint(20) UNSIGNED NOT NULL ,
`ReceiverUserID`  bigint(20) UNSIGNED NOT NULL ,
`Text`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`DateCreated`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
PRIMARY KEY (`ID`),
FOREIGN KEY (`SenderUserID`) REFERENCES `user` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY (`ReceiverUserID`) REFERENCES `user` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
INDEX `SenderUserID` (`SenderUserID`) USING BTREE ,
INDEX `ReceiverUserID` (`ReceiverUserID`) USING BTREE 
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
AUTO_INCREMENT=7

;

-- ----------------------------
-- Records of message
-- ----------------------------
BEGIN;
INSERT INTO `message` VALUES ('1', '1', '2', 'Hey I\'m User #1. How are you?', '2013-10-10 23:43:33'), ('2', '1', '4', 'You should stay away from my woman.', '2013-10-10 23:44:28'), ('3', '1', '4', 'Hey! I told you to stay way!!!', '2013-10-10 23:44:43'), ('4', '1', '1', 'It\'s like...looking into a mirror.', '2013-10-10 23:45:03'), ('5', '2', '1', 'Oh hey what up yo?! ', '2013-10-10 23:45:24'), ('6', '3', '2', 'This place be cray cray.', '2013-10-10 23:46:47');
COMMIT;

-- ----------------------------
-- Table structure for rank
-- ----------------------------
DROP TABLE IF EXISTS `rank`;
CREATE TABLE `rank` (
`ID`  int(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
`Name`  varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
`Ordinal`  mediumint(8) UNSIGNED NOT NULL DEFAULT 0 ,
PRIMARY KEY (`ID`)
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_swedish_ci
AUTO_INCREMENT=4

;

-- ----------------------------
-- Records of rank
-- ----------------------------
BEGIN;
INSERT INTO `rank` VALUES ('1', 'Normal', '0'), ('2', 'Super', '1'), ('3', 'Extravagant', '2');
COMMIT;

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
`ID`  bigint(20) UNSIGNED NOT NULL ,
`RankID`  int(10) UNSIGNED NOT NULL DEFAULT 1 ,
`AccessToken`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`Name`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`DateModified`  timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP ,
PRIMARY KEY (`ID`),
FOREIGN KEY (`RankID`) REFERENCES `rank` (`ID`) ON DELETE RESTRICT ON UPDATE CASCADE,
INDEX `RankID` (`RankID`) USING BTREE 
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci

;

-- ----------------------------
-- Records of user
-- ----------------------------
BEGIN;
INSERT INTO `user` VALUES ('1', '1', '354g34g34g54y3hh', 'Anthony', '2013-09-30 00:24:58'), ('2', '1', 'sdfg4yhwerth46hy', 'Jackson', '2013-09-30 00:25:00'), ('3', '1', 'sdgfg364h3645hfh', 'Frank', '2013-09-30 00:25:02'), ('4', '1', 'w45y2363265hn7hd', 'Richard', '2013-09-30 00:25:06');
COMMIT;

-- ----------------------------
-- Auto increment value for message
-- ----------------------------
ALTER TABLE `message` AUTO_INCREMENT=7;

-- ----------------------------
-- Auto increment value for rank
-- ----------------------------
ALTER TABLE `rank` AUTO_INCREMENT=4;
