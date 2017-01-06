<?php

class Businessfactory_Roihuntereasy_Block_ProductViewAnalytics extends Businessfactory_Roihuntereasy_Block_Database
{
    public function getProduct() {
        return Mage::registry('current_product');
    }
}
