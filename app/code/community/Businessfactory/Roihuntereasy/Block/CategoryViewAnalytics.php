<?php

class Businessfactory_Roihuntereasy_Block_CategoryViewAnalytics extends Businessfactory_Roihuntereasy_Block_Database
{
    public function getProductIds() {
        try {
            $products = Mage::registry('current_category')->getProductCollection();

            // slice array not to list all the products
            $limit = 10;
            $count = 0;
            $productIds = array();
            foreach ($products as $product) {
                array_push($productIds, "mag_".$product->getId());
                if (count($productIds) >= $limit) {
                    break;
                }
                $count = $count + 1;
            }
            return json_encode($productIds);
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, 'errors.log');
            Mage::log($exception, null, 'errors.log');
            return null;
        }

        return ;
    }
}
