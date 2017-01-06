<?php

class Businessfactory_Roihuntereasy_Block_Database extends Mage_Core_Block_Template {

    protected $_Collection = null;

    public function getCollection()
    {
        if(is_null($this->_Collection)){
            $this->_Collection=Mage::getModel('businessfactory_roihuntereasy/main')->getCollection();
        }

        return $this->_Collection;
    }

    public function getConversionId()
    {
        try {
            $collection=$this->getCollection();

            if (($mainItem = ($collection->getLastItem())) == NULL) {
                Mage::log("Table record not found during " . __METHOD__, null, 'errors.log');
                return null;
            }

            if (($conversionId = $mainItem->getConversionId()) == NULL) {
                Mage::log("Conversion ID not found during " . __METHOD__, null, 'errors.log');
                return null;
            }

            return $conversionId;
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, 'errors.log');
            Mage::log($exception, null, 'errors.log');
            return null;
        }
    }
}
