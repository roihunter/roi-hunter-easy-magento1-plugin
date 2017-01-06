<?php

class Businessfactory_Roihuntereasy_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        echo "<h1>Hello Holly.</h1>";
        $this->loadLayout();
        $this->renderLayout();
    }

    public function mamethodeAction()
    {
        echo "test mamethode";
      }
}

