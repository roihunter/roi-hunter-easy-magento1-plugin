<?php

class Businessfactory_Roihuntereasy_Block_AddedToCartAnalytics extends Businessfactory_Roihuntereasy_Block_Database {

    protected $prodId;
    protected $prodPrice;

    public function _toHtml() {
        try {
            $product_remarketing_base64 = Mage::getSingleton("customer/session")->getMyValue();

            $product_remarketing_json = base64_decode($product_remarketing_base64);
            $product_remarketing = json_decode($product_remarketing_json, true);

            if ($product_remarketing && array_key_exists('pagetype', $product_remarketing)) {
                $pagetype = $product_remarketing['pagetype'];

                // render template with remarketing tag
                if ($pagetype === "cart" && $product_remarketing) {
                    $this->prodId = $product_remarketing['id'];
                    $this->prodPrice = $product_remarketing['price'];

                    // unset session value
                    Mage::getSingleton('customer/session')->unsMyValue();

                    return parent::_toHtml();
                }
            }
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, 'errors.log');
            Mage::log($exception, null, 'errors.log');
        }

        return '';
    }

    public function getProdId()
    {
        if (!$this->prodId) {
            Mage::log("Product ID not found during " . __METHOD__, null, 'errors.log');
        }
        return $this->prodId;
    }

    public function getProdPrice()
    {
        if (!$this->prodPrice) {
            Mage::log("Product price not found during " . __METHOD__, null, 'errors.log');
        }
        return $this->prodPrice;
    }
}
