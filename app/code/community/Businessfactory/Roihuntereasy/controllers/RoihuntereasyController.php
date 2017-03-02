<?php

// Admin page controller
class Businessfactory_Roihuntereasy_RoihuntereasyController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout()->_setActiveMenu('businessfactory');
        $this->renderLayout();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('businessfactory/businessfactory_roihuntereasy');
    }
}
