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
INSERT INTO list_options ( list_id, option_id, title, seq, is_default, option_value, mapping, notes ) VALUES ( 'payment_adjustment_code', 'cap_payment', 'Capitation Payment', '100', '', '', '', '' );
CREATE TABLE `insurance_verification` (
  `insurance_id` int(11) DEFAULT NULL,
  `verification_authority` varchar(255) DEFAULT NULL,
  `verification_type` varchar(255) DEFAULT NULL,
  `verification_value` varchar(255) DEFAULT NULL,
  UNIQUE KEY `unique` (`insurance_id`,`verification_authority`,`verification_type`)
) ENGINE=InnoDB;
CREATE TABLE `error_codes` (
  `code_type` varchar(255) DEFAULT NULL,
  `code_from` varchar(255) DEFAULT NULL,
  `code_value` varchar(100) DEFAULT NULL,
  `code_description` varchar(1000) DEFAULT NULL,
  UNIQUE KEY `Unique` (`code_type`,`code_from`,`code_value`)
) ENGINE=InnoDB;
insert  into `error_codes`(`code_type`,`code_from`,`code_value`,`code_description`) values ('x12','availity','42','Unable to Respond at Current Time'),('x12','availity','15','Required application data missing'),('x12','availity','43','Invalid/Missing Provider Identification'),('x12','availity','45','Invalid/Missing Provider Specialty'),('x12','availity','47','Invalid/Missing Provider State'),('x12','availity','48','Invalid/Missing Referring Provider Identification\r\nNumber'),('x12','availity','49','Provider is Not Primary Care Physician'),('x12','availity','51','Provider Not on File'),('x12','availity','52','Service Dates Not Within Provider Plan Enrollment'),('x12','availity','56','Inappropriate Date'),('x12','availity','57','Invalid/Missing Date(s) of Service'),('x12','availity','58','Invalid/Missing Date-of-Birth'),('x12','availity','60','Date of Birth Follows Date(s) of Service'),('x12','availity','61','Date of Death Precedes Date(s) of Service'),('x12','availity','62','Date of Service Not Within Allowable Inquiry Period'),('x12','availity','63','Date of Service in Future'),('x12','availity','64','Invalid/Missing Patient ID'),('x12','availity','65','Invalid/Missing Patient Name'),('x12','availity','66','Invalid/Missing Patient Gender Code'),('x12','availity','67','Patient Not Found'),('x12','availity','68','Duplicate Patient ID Number'),('x12','availity','71','Patient Birth Date Does Not Match That for the \r\nPatient on the Database'),('x12','availity','72','Invalid/Missing Subscriber/Insured ID'),('x12','availity','73','Invalid/Missing Subscriber/Insured Name'),('x12','availity','74','Invalid/Missing Subscriber/Insured Gender Code'),('x12','availity','75','Subscriber/Insured Not Found'),('x12','availity','76','Duplicate Subscriber/Insured ID Number'),('x12','availity','77','Subscriber Found, Patient Not Found'),('x12','availity','78','Subscriber/Insured Not in Group/Plan Identified'),('x12','availity','04','Authorized Quantity Exceeded'),('x12','availity','41','Authorization/Access Restrictions'),('x12','availity','79','Invalid Participant Identification'),('x12','availity','44','Invalid/Missing Provider Name'),('x12','availity','46','Invalid/Missing Provider Phone Number'),('x12','availity','50','Provider Ineligible for Inquiries'),('x12','availity','T4','Payer Name or Identifier Missing'),('x12','availity','53','Inquired Benefit Inconsistent with Provider Type'),('x12','availity','54','Inappropriate Product/Service ID Qualifier'),('x12','availity','55','Inappropriate Product/Service ID'),('x12','availity','69','Inconsistent with Patient’s Age'),('x12','availity','70','Inconsistent with Patient’s Gender');

CREATE TABLE `arr_codes` (
  `ac_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ac_denial_remark` varchar(45) DEFAULT NULL,
  `ac_action` varchar(45) DEFAULT NULL,
  `ac_status` varchar(45) DEFAULT NULL,
  `ac_scenarioa` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`ac_id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8;

insert  into `arr_codes`(`ac_id`,`ac_denial_remark`,`ac_action`,`ac_status`,`ac_scenarioa`) values (11,'No claim on file','Resubmission','Collectable/AR',1),(12,'Authorization','Auth#','Provider/Client',19),(13,'Non covered service ','Bill to pt','Pt response',8),(14,'Coverage terminated','Bill to pt','Pt response',25),(15,'Included/Global','Appeal','Collectable/AR',9),(16,'Invalid Modifier','Appeal','Collectable/AR',13),(17,'Invalid DX','Appeal','Collectable/AR',24),(18,'Invalid CPT','Appeal','Collectable/AR',24),(19,'Invalid POS','Appeal','Collectable/AR',24),(20,'Deductable','Bill to pt','Pt response',5),(21,'Lack of information','Follow up','Collectable/AR',18),(22,'Copay /Coins','Bill to pt','Pt response',NULL),(23,'COB info','Resubmission','Collectable/AR',15),(24,'Referral or Auth','Auth#','Provider/Client',26),(25,'Claim in Process','In Process','In process',3),(26,'Medically not necessity','Appeal','Collectable/AR',20),(27,'Frequency','Appeal','Collectable/AR',27),(28,'Paid ','Waiting for eob','Waiting for eob',28),(29,'Paid ','Zero blance','Done',NULL),(30,'HMO','Resubmission','Collectable/AR',29),(31,'Voice mail/Fax back','Follow up','Follow up',NULL),(32,'Hospice','Resubmission','Collectable/AR',27),(33,'Pre-existing info ','Pt Info','Patient/ Info',14),(34,'Pre-existing info ','Provider info','Provider/Client',14),(35,'Past Timely filing','Appeal','Collectable/AR',21),(36,'Past Appeal Limit','W/off','W/off',6),(37,'PATIENT NOT ELIGIBLE ','Bill to pt','Pt response',7),(38,'CAPITATION  ','Waiting for eob','Waiting for eob',23),(39,'DUPLICATE','Follow up','Follow up',10),(40,'MEDICAL NOTES','Appeal','Collectable/AR',11),(41,'maximum benefits met','Bill to pt','Pt response',12),(42,'Primary Eob ','Resubmission','Collectable/AR',16),(43,'Patient Cannot be identified','Bill to pt','Pt response',22),(44,'Incomplet HCFA Form','Resubmission','Collectable/AR',30),(45,'Invalid NPI','Bill to pt','Pt response',NULL),(46,'W/off','W/off','W/off',NULL),(47,'OON','Bill to Patient','Patient Responsibility',NULL);