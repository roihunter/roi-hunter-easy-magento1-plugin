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
            $configurableParentItems = array();
            $configurableChildItems = array();

            $collection = Mage::getResourceModel('sales/order_collection')
                ->addFieldToFilter('entity_id', array('in' => $orderIds));

            foreach ($collection as $order) {
                $conversionValue += $order->getBaseGrandTotal();

                // returns all order items
                // configurable items are separated to two items - one simple with parent_item_id and one configurable with item_id
                $items = $order->getAllItems();
                foreach ($items as $item) {
                    $parent_item_id = $item->getParentItemId();
                    $product_type = $item->getProductType();
                    
                    if ($parent_item_id == null) {
                        // simple product - write directly to the result IDs array
                        if ($product_type == "simple") {
                            array_push($productIds, "mag_".$item->getProductId());
                            Mage::log("Writing simple product", null, 'debug.log');
                        }
                        // configurable parent product
                        else if ($product_type == "configurable") {
                            array_push($configurableParentItems, $item);
                            Mage::log("Storing configurable parent product", null, 'debug.log');
                        }
                    }
                    // configurable child product
                    else {
                        array_push($configurableChildItems, $item);
                        Mage::log("Storing configurable child product", null, 'debug.log');
                    }
                }
            }

            // create map of parent IDS : parent objects
            $parentItemIdToProductIdMap = array();
            foreach ($configurableParentItems as $item) {
                $parentItemIdToProductIdMap[$item['item_id']] = $item['product_id'];
            }

            Mage::log("Configurable parent products map: %s" % ($parentItemIdToProductIdMap), null, 'debug.log');

            // iterate over children items a find parent item in the map
            foreach ($configurableChildItems as $item) {
                $id = "mag_".$parentItemIdToProductIdMap[$item["parent_item_id"]]."_".$item["product_id"];
                array_push($productIds, $id);
            }

            // create Google Adwords data
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
