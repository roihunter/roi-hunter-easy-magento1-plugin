<?php

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('businessfactory_roihuntereasy/main'),
        'conversion_label',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT, 255,
            'nullable' => true,
            'default' => null,
            'length' => 255,
            'comment' => 'Conversion Label'
        )
    );

$installer->endSetup();