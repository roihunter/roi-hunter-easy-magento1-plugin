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

                $resultCode = $this->cron->generateSupportedFeeds();
                if($resultCode == true){
                    $response->setBody(json_encode('Feeds generated.'));
                } else {
                    $response->setBody(json_encode('One or more feeds not generated. See logs for more info.'));
                }
            } catch (Exception $exception) {
                Mage::log(__METHOD__ . " exception.", null, 'errors.log');
                Mage::log($exception->getMessage(), null, 'errors.log');
                Mage::log($request, null, 'errors.log');
                $response->setHttpResponseCode(500);
                $response->setBody(json_encode('Feed generation failed.'));
            }
        } else if ($request->getMethod() === 'OPTIONS') {

        } else {
            $response->setHttpResponseCode(400);
        }
    }

    /**
     * http://store.com/roihuntereasy/cron/init
     */
    public function initAction()
    {
        Mage::log(__METHOD__ . "- FeedReset called.");

        $request = $this->getRequest();
        $response = $this->getResponse();

        Mage::log($request);

        $response->setHeader('Content-type', 'application/json');
        $response->setHeader('Access-Control-Allow-Origin', '*', true);
        $response->setHeader('Access-Control-Allow-Methods', 'OPTIONS,GET', true);
        $response->setHeader('Access-Control-Max-Age', '60', true);
        $response->setHeader('Access-Control-Allow-Headers', 'X-Authorization', true);

        if ($request->getMethod() === 'GET') {
            try {
                // If table not empty, require authorization.
                $mainItemCollection = Mage::getModel('businessfactory_roihuntereasy/main')->getCollection();
                if ($mainItemCollection->count() > 0) {
                    $authorizationHeader = $this->getRequest()->getHeader('X-Authorization');
                    $dataEntity = $mainItemCollection->getLastItem();
                    // If data exist check for client token.
                    if ($dataEntity->getClientToken() != null && $dataEntity->getClientToken() !== $authorizationHeader) {
                        $response->setBody(json_encode("Not authorized"));
                        $response->setHttpResponseCode(403);
                        return;
                    }
                }

                // remove file locks
                foreach ($this->supportedFileFormats as $fileFormat) {
                    $filename = "businessFactoryRoiHunterEasyFeedSign".$fileFormat;
                    $io = new Varien_Io_File();
                    $io->open(array('path' => Mage::getBaseDir()));

                    if (!$io->fileExists($filename)) {
                        $response->setBody(json_encode("Reset already completed."));
                    }
                    else {
                        // try to delete feed generation sign.
                        $io->rm($filename);
                        $response->setBody(json_encode("Reset completed."));
                    }
                }

                // regenerate feeds
                $resultCode = $this->cron->generateSupportedFeeds();
                if($resultCode == true){
                    $response->setBody(json_encode('Feeds generated.'));
                } else {
                    $response->setBody(json_encode('One or more feeds not generated. See logs for more info.'));
                }
            } catch (Exception $exception) {
                Mage::log(__METHOD__ . " exception.", null, 'errors.log');
                Mage::log($exception, null, 'errors.log');
                Mage::log($request, null, 'errors.log');
                $response->setHttpResponseCode(500);
                $response->setBody(json_encode('Feed generation failed.'));
            }
        } else if ($request->getMethod() === 'OPTIONS') {

        } else {
            $response->setHttpResponseCode(400);
        }
    }
}

