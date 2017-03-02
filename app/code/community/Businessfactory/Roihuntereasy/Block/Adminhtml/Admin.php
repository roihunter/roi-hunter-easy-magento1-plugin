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

    public function getStoreBaseUrl()
    {
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, true);
    }

    public function getStoreName()
    {
        return $this->store->getFrontendName();
    }

    public function getStoreLogo()
    {
        return $this->store->getLogoSrc();
    }

    public function getStoreCurrency()
    {
        return $this->store->getCurrentCurrencyCode();
    }

    public function getStoreLanguage()
    {
        // http://stackoverflow.com/questions/6579287/magento-get-language-code-in-template-file
        $locale = explode("_", Mage::app()->getLocale()->getLocaleCode());
        return $locale[0];
    }

    public function getStoreCountry()
    {
        $locale = explode("_", Mage::app()->getLocale()->getLocaleCode());
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
