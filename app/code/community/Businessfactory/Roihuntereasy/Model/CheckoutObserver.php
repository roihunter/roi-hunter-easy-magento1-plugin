<?php

class Businessfactory_Roihuntereasy_Model_CheckoutObserver extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        $this->_init("roihuntereasy/roihuntereasy");
    }

    public function setRemarketingTag(Varien_Event_Observer $observer)
    {
        try {
            $orderIds = $observer->getEvent()->getOrderIds();

            if (!$orderIds || !is_array($orderIds)) {
                return $this;
            }

            $conversionValue = 0;
            $currency = null;
            $productIds = array();
            $configurableParentItems = array();
            $configurableChildItems = array();

            $collection = Mage::getResourceModel("sales/order_collection")
                ->addFieldToFilter("entity_id", array("in" => $orderIds));

            foreach ($collection as $order) {
                $conversionValue += $order->getBaseGrandTotal();
                $currency = $order->getStoreCurrencyCode();

                // returns all order items
                // configurable items are divided into two items - one simple with parent_item_id and one configurable with item_id
                $items = $order->getAllItems();
                foreach ($items as $item) {
                    $parentItemId = $item->getParentItemId();
                    $productType = $item->getProductType();
                    
                    if ($parentItemId == null) {
                        // simple product - write directly to the result IDs array
                        if ($productType == "simple") {
                            array_push($productIds, "mag_".$item->getProductId());
                            Mage::log("Writing simple product", null, "debug.log");
                        }
                        // configurable parent product
                        else if ($productType == "configurable") {
                            array_push($configurableParentItems, $item);
                            Mage::log("Storing configurable parent product", null, "debug.log");
                        }
                    }
                    // configurable child product
                    else {
                        array_push($configurableChildItems, $item);
                        Mage::log("Storing configurable child product", null, "debug.log");
                    }
                }
            }

            // create map of parent IDS : parent objects
            $parentItemIdToProductIdMap = array();
            foreach ($configurableParentItems as $item) {
                $parentItemIdToProductIdMap[$item["item_id"]] = $item["product_id"];
            }

            // iterate over children items a find parent item in the map
            foreach ($configurableChildItems as $item) {
                $id = "mag_".$parentItemIdToProductIdMap[$item["parent_item_id"]]."_".$item["product_id"];
                array_push($productIds, $id);
            }

            // create Google Adwords data
            $checkoutRemarketingData = array(
                "pagetype" => "checkout",
                "id" => $productIds,
                "price" => $conversionValue,
                "currency" => $currency
            );

            Mage::log("Setting temporary customer session value: ".json_encode($checkoutRemarketingData), null, "debug.log");

            $checkoutRemarketingJson = json_encode($checkoutRemarketingData);
            $checkout_RemarketingBase64 = base64_encode($checkoutRemarketingJson);

            // set session
            Mage::getSingleton("customer/session")->setMyValue($checkout_RemarketingBase64);

            return $this;
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, "errors.log");
            Mage::log($exception, null, "errors.log");
        }
    }
}
