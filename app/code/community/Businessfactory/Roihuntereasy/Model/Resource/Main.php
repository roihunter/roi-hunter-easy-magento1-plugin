<?php

class Businessfactory_Roihuntereasy_Model_Resource_Main extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('businessfactory_roihuntereasy/main', 'id');
    }
}