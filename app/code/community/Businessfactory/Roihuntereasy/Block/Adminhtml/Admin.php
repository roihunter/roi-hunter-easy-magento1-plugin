<?php

class Businessfactory_Roihuntereasy_Block_Adminhtml_Admin extends Mage_Adminhtml_Block_Template {

    protected $store;

    function _construct() {
        $this->store = Mage::app()->getStore();
    }

    public function getDevelopmentMode()
    {
        return Mage::getIsDeveloperMode() ? "developer" : "production";
    }

    public function isStagingActive()
    {
        $filename = "roi_hunter_staging_active";
        $io = new Varien_Io_File();
        $io->open(array("path" => Mage::getBaseDir()));
        if ($io->fileExists($filename)) {
            return true;
        }
        else {
            return false;
        }
    }

    public function getIframeBaseUrl()
    {
        if ($this->isStagingActive()) {
            return "//goostav-fe-staging.roihunter.com/";
        }
        else {
            return "//magento.roihunter.com/";
        }
    }

    public function getDefaultStoreId()
    {
        return Mage::app()
            ->getWebsite(true)
            ->getDefaultGroup()
            ->getDefaultStoreId();;
    }

    public function getStoreBaseUrl()
    {

        return Mage::app()->getStore($this->getDefaultStoreId())->getBaseUrl();
    }

    public function getStoreName()
    {
        return Mage::app()->getStore($this->getDefaultStoreId())->getFrontendName();
    }

    public function getStoreLogo()
    {
        return Mage::app()->getStore($this->getDefaultStoreId())->getLogoSrc();
    }

    public function getStoreCurrency()
    {
        return Mage::app()->getStore($this->getDefaultStoreId())->getCurrentCurrencyCode();
    }

    public function getPluginVersion()
    {
        return (string) Mage::getConfig()->getNode()->modules->Businessfactory_Roihuntereasy->version;
    }

    public function getStoreLanguage()
    {
        // http://stackoverflow.com/questions/6579287/magento-get-language-code-in-template-file
        $locale = explode("_", Mage::getStoreConfig('general/locale/code', $this->getDefaultStoreId()));
        return $locale[0];
    }

    public function getStoreCountry()
    {
        $locale = explode("_", Mage::getStoreConfig('general/locale/code', $this->getDefaultStoreId()));
        if (is_array($locale) && count($locale) > 1) {
            return $locale[1];
        } else {
            return "US";
        }

    }

    function getMainItemEntry()
    {
        $mainItemCollection = Mage::getModel('businessfactory_roihuntereasy/main')->getCollection();
        if ($mainItemCollection->count() <= 0) {
            return null;
        } else {
            return $mainItemCollection->getLastItem();
        }
    }

}
