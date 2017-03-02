<?php

class Businessfactory_Roihuntereasy_Adminhtml_RoihuntereasyController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Return some checking result
     *
     * @return void
     */
    public function checkAction() {
        $result = 1;
        Mage::app()->getResponse()->setBody($result);
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('businessfactory/businessfactory_roihuntereasy');
    }
}

