<?php

// Admin page controller
class Businessfactory_Roihuntereasy_RoihuntereasyController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout()->_setActiveMenu('businessfactory');
        $this->renderLayout();
    }
}
