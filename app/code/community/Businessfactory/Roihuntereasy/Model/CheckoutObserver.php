<?php

class Businessfactory_Roihuntereasy_Model_CheckoutObserver extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        $this->_init('roihuntereasy/roihuntereasy');
    }

    public function setRemarketingTag(Varien_Event_Observer $observer)
    {
        try {
            $orderIds = $observer->getEvent()->getOrderIds();

            if (!$orderIds || !is_array($orderIds)) {
                return $this;
            }

            $conversionValue = 0;
            $productIds = array();

            $collection = Mage::getResourceModel('sales/order_collection')
                ->addFieldToFilter('entity_id', array('in' => $orderIds));

            foreach ($collection as $order) {
                $conversionValue += $order->getBaseGrandTotal();

                $products = $order->getAllVisibleItems();
                foreach ($products as $product) {
                    array_push($productIds, $product->getSku());
                }
            }

            $checkout_remarketing_data = array(
                'pagetype' => 'checkout',
                'id' => $productIds,
                'price' => $conversionValue
            );
            $checkout_remarketing_json = json_encode($checkout_remarketing_data);
            $checkout_remarketing_base64 = base64_encode($checkout_remarketing_json);

            // set session
            Mage::getSingleton('customer/session')->setMyValue($checkout_remarketing_base64);

            return $this;
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, 'errors.log');
            Mage::log($exception, null, 'errors.log');
        }
    }
}
