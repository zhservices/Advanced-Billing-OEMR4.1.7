CREATE TABLE `authorization` (
  `auth_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `auth_pid` INT(10) UNSIGNED NOT NULL,
  `auth_ins_id` INT(10) UNSIGNED NOT NULL,
  `auth_ins_data_id` INT(10) UNSIGNED NOT NULL,
  `auth_from` DATE DEFAULT '0000-00-00',
  `auth_to` DATE DEFAULT '0000-00-00',
  `auth_no_of_visits` SMALLINT(5) UNSIGNED DEFAULT '0',
  `auth_note` VARCHAR(100) NOT NULL,
  `auth_cpt` VARCHAR(15) NOT NULL,
  `auth_is_active` TINYINT(3) UNSIGNED NOT NULL DEFAULT '1',
  `auth_no` VARCHAR(45) NOT NULL,
  `auth_form_no` SMALLINT(5) UNSIGNED NOT NULL,
  `auth_author` VARCHAR(100) DEFAULT NULL,
  `auth_timestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `auth_version` SMALLINT(6) DEFAULT NULL,
  `auth_visit_type` VARCHAR(50) DEFAULT NULL,
  `auth_tot_no_of_visits` SMALLINT(5) UNSIGNED DEFAULT NULL,
  `auth_unlimited` ENUM('n','y') DEFAULT 'n',
  PRIMARY KEY (`auth_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;
CREATE TABLE `capitation_master` (
  `cm_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto Increment',
  `cm_insid` INT(11) DEFAULT NULL COMMENT 'Insurance ID',
  `cm_billing_facId` INT(11) DEFAULT NULL COMMENT 'Capitation billing Facility',
  `cm_start_date` DATE DEFAULT NULL COMMENT 'Starting date of Capitation Contract(Must Field)',
  `cm_end_date` DATE DEFAULT NULL COMMENT 'Ending date of Capitation Contract(blank means infinite)',
  `cm_payment_frequency` VARCHAR(50) DEFAULT '' COMMENT 'Monthly,bimonthly,half yearly,yearly    will be a list item',
  `cm_activity` TINYINT(4) DEFAULT '1' COMMENT 'is presentrly active or not 0 means not active, 1 means active',
  `cm_provider` INT(11) DEFAULT NULL,
  PRIMARY KEY (`cm_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;
CREATE TABLE `external_modules` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` INT(10) UNSIGNED NOT NULL COMMENT '5->random password',
  `field_value` VARCHAR(255) NOT NULL,
  `created_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;
CREATE TABLE `arr_master` (
  `am_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `am_uid` INT(10) UNSIGNED DEFAULT NULL,
  `am_pid` INT(10) UNSIGNED DEFAULT NULL,
  `am_encounter` INT(10) UNSIGNED DEFAULT NULL,
  `am_callnotes` TEXT,
  `am_denialremark` VARCHAR(45) DEFAULT NULL,
  `am_action` VARCHAR(45) DEFAULT NULL,
  `am_status` VARCHAR(45) DEFAULT NULL,
  `am_callback` INT(10) UNSIGNED DEFAULT NULL,
  `am_statustrack` INT(10) DEFAULT NULL,
  `am_currentstatus` INT(10) DEFAULT NULL,
  `am_clearinghouse_status` VARCHAR(25) DEFAULT NULL,
  `am_date` DATETIME DEFAULT NULL,
  `am_assigner` INT(10) DEFAULT NULL,
  `am_assigned_user` INT(10) UNSIGNED DEFAULT NULL,
  `am_ins1` BIGINT(20) DEFAULT NULL,
  `am_ins2` BIGINT(20) DEFAULT NULL,
  `am_ins3` BIGINT(20) DEFAULT NULL,
  `am_inslevel` TINYINT(4) DEFAULT NULL,
  `am_calldate` DATE DEFAULT NULL,
  `am_callbackdate` DATE DEFAULT NULL,
  PRIMARY KEY (`am_id`),
  KEY `am_encounter` (`am_encounter`),
  KEY `am_pid` (`am_pid`),
  KEY `am_statustrack` (`am_statustrack`)
) ENGINE=INNODB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
CREATE TABLE `arr_status` (
  `ac_id` INT(11) NOT NULL AUTO_INCREMENT,
  `ac_uid` INT(10) DEFAULT NULL,
  `ac_pid` INT(10) DEFAULT NULL,
  `ac_encounter` INT(10) DEFAULT NULL,
  `ac_arr_id` INT(10) DEFAULT NULL,
  `ac_master_status` INT(10) DEFAULT NULL,
  `ac_status` INT(10) DEFAULT NULL,
  `ac_arstatus` INT(10) DEFAULT NULL,
  `ac_officeally_status` INT(10) DEFAULT NULL,
  `ac_callnotes` TEXT,
  `ac_am_denial_remark` VARCHAR(45) DEFAULT NULL,
  `ac_am_action` VARCHAR(45) DEFAULT NULL,
  `ac_callbackdays` INT(10) DEFAULT NULL,
  `ac_callbackdate` DATE DEFAULT NULL,
  `ac_count` SMALLINT(6) DEFAULT NULL,
  `ac_timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ac_comment` TEXT,
  `ac_reason` TEXT,
  `ac_am_status` VARCHAR(45) DEFAULT NULL,
  PRIMARY KEY (`ac_id`),
  KEY `ac_pid` (`ac_pid`),
  KEY `ac_encounter` (`ac_encounter`),
  KEY `ac_arr_id` (`ac_arr_id`)
) ENGINE=INNODB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
CREATE TABLE `era_details` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `provider_id` VARCHAR(20) DEFAULT NULL,
  `check_number` VARCHAR(50) DEFAULT NULL,
  `payer_name` VARCHAR(50) DEFAULT NULL,
  `data` TEXT,
  `filename` VARCHAR(40) DEFAULT NULL,
  `datetime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `processed` TINYINT(4) DEFAULT '0' COMMENT '0-Pending 1-Processed',
  `check_amount` FLOAT DEFAULT NULL,
  `filename_html` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=INNODB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
CREATE TABLE  `module_report_master` (
  `mm_id` int(10) unsigned NOT NULL auto_increment,
  `mm_rid` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `gacl_id` varchar(45) default NULL,
  PRIMARY KEY  (`mm_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;
ALTER TABLE `billing` CHANGE `modifier` `modifier` VARCHAR(19);
ALTER TABLE `ar_session` ADD COLUMN `cap_bill_facId` SMALLINT(5) UNSIGNED NULL;
ALTER TABLE `era_details` ADD UNIQUE `NewIndex1` (`check_number`);
ALTER TABLE `x12_partners` ADD COLUMN `x12_username` VARCHAR(30) NULL;
ALTER TABLE `x12_partners` ADD COLUMN `x12_password` VARCHAR(30) NULL;
ALTER TABLE `x12_partners` ADD COLUMN `last_download_date` DATETIME NULL;
ALTER TABLE `ar_activity` ADD INDEX `encounter`(`encounter`);
ALTER TABLE `billing` ADD INDEX `encounter`(`encounter`);
ALTER TABLE `form_encounter` ADD INDEX `encounter`(`encounter`);
ALTER TABLE `patient_data` ADD INDEX `pid`(`pid`);
ALTER TABLE `patient_data` ADD INDEX `lname`(`lname`);
ALTER TABLE `patient_data` ADD INDEX `fname`(`fname`);
ALTER TABLE `insurance_data` ADD COLUMN `auth_required` TINYINT(4) UNSIGNED;
ALTER TABLE `form_encounter` ADD COLUMN `pos_code` INT(10) UNSIGNED NOT NULL;
ALTER TABLE `form_encounter` ADD COLUMN `assignment` TINYINT(3) UNSIGNED NOT NULL default 1;
ALTER TABLE `form_encounter` ADD COLUMN `encounter_provideID` BIGINT(14) NULL;
ALTER TABLE `patient_data` ADD COLUMN `onset_hospitaliztion` VARCHAR(255) NULL AFTER `soap_import_status`;
ALTER TABLE `customlists` CHANGE `cl_list_item_short` `cl_list_item_short` VARCHAR(25) NULL ;
ALTER TABLE `billing` ADD COLUMN `is_capitation` TINYINT DEFAULT '0' NULL;
ALTER TABLE `billing` ADD COLUMN `auth_id` VARCHAR(45) NULL;
ALTER TABLE `billing` ADD COLUMN `for_advanced_cpt` TINYINT DEFAULT '0' NULL;
ALTER TABLE `billing` ADD COLUMN `notecodes` VARCHAR(25) NULL;
ALTER TABLE `era_details` ADD UNIQUE `NewIndex1` (`check_number`);
ALTER TABLE `billing` CHANGE `modifier` `modifier` VARCHAR(19);
ALTER TABLE `insurance_companies` ADD COLUMN `is_group` TINYINT NULL;
ALTER TABLE `insurance_companies` ADD COLUMN `group_id` INT NULL;
ALTER TABLE `ar_session` ADD COLUMN `cap_from_date` DATE NULL;
ALTER TABLE `ar_session` ADD COLUMN `cap_to_date` DATE NULL;
ALTER TABLE `ar_activity` CHANGE `modifier` `modifier` VARCHAR(19) CHARSET utf8 COLLATE utf8_general_ci NOT NULL;
insert into `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`) values('Payment_Frequency','monthly','Monthly','0','1','0','','');
insert into `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`) values('Payment_Frequency','bimonthly','Bimonthly','0','0','0','','');
insert into `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`) values('Payment_Frequency','halfyearly','Half Yearly','0','0','0','','');
insert into `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`) values('Payment_Frequency','yearly','Yearly','0','0','0','','');
insert into `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`) values('lists','Payment_Frequency','Payment Frequency','0','0','0','','');
ALTER TABLE `era_details`     CHANGE `check_number` `check_number` VARCHAR(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL ; 