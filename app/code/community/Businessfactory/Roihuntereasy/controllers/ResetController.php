<?php

class Businessfactory_Roihuntereasy_ResetController extends Mage_Core_Controller_Front_Action
{
    /**
     * http://store.com/roihuntereasy/reset/data
     */
    public function dataAction()
    {
        Mage::log(__METHOD__ . " invoked", null, 'debug.log');

        $response = $this->getResponse();

        try {
            // If no items -> success
            $mainItemCollection = Mage::getModel('businessfactory_roihuntereasy/main')->getCollection();
            if ($mainItemCollection->count() <= 0) {
                return $response->setBody(json_encode("Reset completed"));
            } else {
                // Delete all plugin data
                $accessToken = $mainItemCollection->getLastItem()->getAccessToken();

                foreach ($mainItemCollection as $mainItem) {
                    Mage::getModel('businessfactory_roihuntereasy/main')->load($mainItem->getId())->delete();
                }

                $client = new Varien_Http_Client('https://goostav.roihunter.com/customers/disconnect');
                $client->setMethod(Varien_Http_Client::GET);
                $client->setConfig(array(
                    'adapter' => 'Zend_Http_Client_Adapter_Curl',
                    'curloptions' => array(CURLOPT_FOLLOWLOCATION => true),
                    'maxredirects' => 5,
                    'timeout' => 60
                ));
                $client->setHeaders(array(
                    'X-Authorization' => $accessToken
                ));

                try{
                    $goostav_response = $client->request();
                    if ($goostav_response->isSuccessful()) {
                        return $response->setBody(json_encode("Reset data completed"));
                    }
                    else {
                        return $response->setBody($goostav_response->getStatus());
                    }
                } catch (Exception $e) {
                    Mage::log($e, null, 'errors.log');
                    return $response->setBody(json_encode("Reset failed. Please check logs and contact support at easy@roihunter.com."));
                }
            }
        } catch (Exception $e) {
            Mage::log($e, null, 'errors.log');
            return $response->setBody(json_encode("Reset failed. Please check logs and contact support at easy@roihunter.com."));
        }
    }

    /**
     * http://store.com/roihuntereasy/reset/cron
     */
    public function cronAction()
    {
        Mage::log(__METHOD__ . " invoked", null, 'debug.log');

        $response = $this->getResponse();

        try {
            $filename = "businessFactoryRoiHunterEasyFeedSign";
            $io = new Varien_Io_File();
            $io->open(array('path' => Mage::getBaseDir()));

            if ($io->fileExists($filename)) {
                $io->rm($filename);
                return $response->setBody(json_encode("Reset cron completed."));
            }
            else {
                return $response->setBody(json_encode("Reset already completed."));
            }
            $io->close();
        } catch (Exception $e) {
            Mage::log($e, null, 'errors.log');
            return $response->setBody(json_encode("Reset cron failed. Please check logs and contact support at easy@roihunter.com."));
        }
    }

}

