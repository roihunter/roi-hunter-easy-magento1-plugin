<?php

class Businessfactory_Roihuntereasy_Block_CheckoutAnalytics extends Businessfactory_Roihuntereasy_Block_Database {

    protected $prodId;
    protected $prodPrice;
    protected $conversionCurrency;

    public function _toHtml() {
        try {
            // find out if session was set
            $productRemarketingBase64 = Mage::getSingleton("customer/session")->getMyValue();
            $productRemarketingJson = base64_decode($productRemarketingBase64);
            $productRemarketing = json_decode($productRemarketingJson, true);

            if ($productRemarketing && array_key_exists("pagetype", $productRemarketing)) {
                $pagetype = $productRemarketing["pagetype"];

                // render template with remarketing tag
                if ($pagetype === "checkout" && $productRemarketing) {
                    $this->prodId = $productRemarketing["id"];
                    $this->prodPrice = $productRemarketing["price"];
                    $this->conversionCurrency = $productRemarketing["currency"];

                    // unset session value
                    Mage::getSingleton("customer/session")->unsMyValue();

                    return parent::_toHtml();
                }
            }
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, "errors.log");
            Mage::log($exception->getMessage(), null, "errors.log");
        }

        return "";
    }

    public function getProdId()
    {
        if (!$this->prodId) {
            Mage::log("Product ID not found during " . __METHOD__, null, "errors.log");
        }
        return json_encode($this->prodId);
    }

    public function getProdPrice()
    {
        if (!$this->prodPrice) {
            Mage::log("Product price not found during " . __METHOD__, null, "errors.log");
        }
        return $this->prodPrice;
    }

    public function getConversionLabel()
    {
        try {
            $collection=$this->getCollection();

            if (($mainItem = ($collection->getLastItem())) == NULL) {
                Mage::log("Table record not found during " . __METHOD__, null, "errors.log");
                return null;
            }

            if (($conversionId = $mainItem->getConversionLabel()) == NULL) {
                Mage::log("Conversion ID not found during " . __METHOD__, null, "errors.log");
                return null;
            }

            return $conversionId;
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, "errors.log");
            Mage::log($exception->getMessage(), null, "errors.log");
            return null;
        }
    }

    public function getConversionCurrency()
    {
        if (!$this->conversionCurrency) {
            Mage::log("Conversion currency not found during " . __METHOD__, null, "errors.log");
        }
        return $this->conversionCurrency;
    }
}
