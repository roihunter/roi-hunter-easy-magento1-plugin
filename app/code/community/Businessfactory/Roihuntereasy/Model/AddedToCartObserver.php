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
            $quoteItem = $observer->getEvent()->getQuoteItem();
            $quoteItemProduct= $quoteItem->getProduct();

            if ($product->getTypeId() == "configurable") {
                $id = "mag_".$quoteItemProduct->getParentId()."_".$quoteItemProduct->getEntityId();
            }
            else {
                $id = "mag_".$quoteItemProduct->getEntityId();
            }
            $price = $product->getFinalPrice();


//            Mage::log($product->toJson(), null, 'debug.log');
//            Mage::log($quoteItem->toJson(), null, 'debug.log');

            // set product as session data
            $product_remarketing_data = array(
                'pagetype' => 'cart',
                'id' => $id,
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
