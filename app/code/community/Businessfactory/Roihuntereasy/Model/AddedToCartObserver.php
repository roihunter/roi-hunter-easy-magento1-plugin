<?php

class Businessfactory_Roihuntereasy_Model_AddedToCartObserver extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        $this->_init('roihuntereasy/roihuntereasy');
    }

    public function setRemarketingTag(Varien_Event_Observer $observer)
    {
        try {
            $product = $observer->getEvent()->getProduct();

            $sku = $product->getSku();
            $price = $product->getFinalPrice();

            // set product as session data
            $product_remarketing_data = array(
                'pagetype' => 'cart',
                'id' => $sku,
                'price' => $price
            );
            $product_remarketing_json = json_encode($product_remarketing_data);
            $product_remarketing_base64 = base64_encode($product_remarketing_json);

            // set session
            Mage::getSingleton('customer/session')->setMyValue($product_remarketing_base64);
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, 'errors.log');
            Mage::log($exception, null, 'errors.log');
        }
    }
}
