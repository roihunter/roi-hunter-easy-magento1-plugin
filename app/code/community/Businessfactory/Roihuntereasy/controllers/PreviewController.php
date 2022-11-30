<?php

class Businessfactory_Roihuntereasy_PreviewController extends Mage_Core_Controller_Front_Action
{
    protected $cron;

    public function _construct()
    {
        parent::_construct();
        $this->cron = new Businessfactory_Roihuntereasy_Model_Cron();
    }

    /**
     * http://store.com/roihuntereasy/preview/preview
     */
    public function previewAction()
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
                $limit = $this->getRequest()->getParam("limit");
                if (!isset($limit) || trim($limit)==='') {
                    $limit = 3;
                }

                $resultPreviewArray = $this->cron->generatePreview($limit);
                if($resultPreviewArray == true){
                    $response->setHeader('Content-type','application/json',true);
                    $response->setBody(json_encode($resultPreviewArray));
                } else {
                    $response->setBody(json_encode('Preview was not generated. See logs for more info.'));
                }
            } catch (Exception $exception) {
                Mage::log(__METHOD__ . " exception.", null, 'errors.log');
                Mage::log($exception->getMessage(), null, 'errors.log');
                Mage::log($request, null, 'errors.log');
                $response->setHttpResponseCode(500);
                $response->setBody(json_encode('Preview generation failed.'));
            }
        } else if ($request->getMethod() === 'OPTIONS') {

        } else {
            $response->setHttpResponseCode(400);
        }
    }
}

