<?php

$installer=$this;

$installer->startSetup();

$installer->run("
-- DROP TABLE IF EXISTS {$this->getTable('businessfactory_roihuntereasy/main')};
CREATE TABLE {$this->getTable('businessfactory_roihuntereasy/main')} (
  `id` int(11) unsigned NOT NULL auto_increment COMMENT 'ID',
   `description` varchar(255) NULL COMMENT 'Description',
   `google_analytics_ua` varchar(255) NULL COMMENT 'Google Analytics UA',
   `customer_id` varchar(255) NULL COMMENT 'Customer Id',
   `access_token` varchar(255) NULL COMMENT 'Access Token',
   `client_token` varchar(255) NULL COMMENT 'Client Token',
   `conversion_id` int(11) NOT NULL COMMENT 'Conversion id',
   `managed_merchants` tinyint(1) NULL COMMENT 'Managed merchants by us',
   `adult_oriented` tinyint(1) NULL COMMENT 'Adult oriented',
   `status` varchar(255) NULL COMMENT 'Goostav status',
   `errors` text NULL COMMENT 'Errors',
   `creation_state` varchar(255) NULL COMMENT 'Creation State',
   `creation_time` timestamp NULL COMMENT 'Creation Time',
   `update_time` timestamp NULL COMMENT 'Modification Time',
   `is_active` smallint(6) NULL COMMENT 'Is Active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup();
