<?php

class Businessfactory_Roihuntereasy_CronController extends Mage_Core_Controller_Front_Action
{
    protected $cron;

    public function _construct()
    {
        parent::_construct();
        $this->cron = new Businessfactory_Roihuntereasy_Model_Cron();
    }

    /**
     * http://store.com/roihuntereasy/cron/index
     */
    public function indexAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        $response->setHeader('Content-type', 'application/json');
        $response->setHeader('Access-Control-Allow-Origin', '*', true);
        $response->setHeader('Access-Control-Allow-Methods', 'OPTIONS,GET', true);
        $response->setHeader('Access-Control-Max-Age', '60', true);
        $response->setHeader('Access-Control-Allow-Headers', 'X-Authorization', true);

        if ($request->getMethod() === 'GET') {
            $this->processGET();
        } else if ($request->getMethod() === 'OPTIONS') {

        } else {
            $response->setHttpResponseCode(400);
        }
    }

    /**
     * GET
     * http://store.com/roihuntereasy/cron/index
     */
    function processGET()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        try {
            $authorizationHeader = $request->getHeader('X-Authorization');

            $mainItemCollection = Mage::getModel('businessfactory_roihuntereasy/main')->getCollection();
            $clientToken = $mainItemCollection->getLastItem()->getClientToken();
            if ($clientToken == NULL || $clientToken !== $authorizationHeader) {
                $response->setBody(json_encode("Not authorized"));
                $response->setHttpResponseCode(403);
                return;
            }

            Mage::log("Cron generating started manually.", null, 'cron.log');
            $resultCode = $this->cron->createFeed();
            if($resultCode == true){
                $response->setBody(json_encode('Feed generated.'));
            } else {
                $response->setBody(json_encode('Feed not generated.'));
            }
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, 'errors.log');
            Mage::log($exception, null, 'errors.log');
            Mage::log($request, null, 'errors.log');
            $response->setHttpResponseCode(500);
            $response->setBody(json_encode('Feed generation failed.'));
        }
    }
}

