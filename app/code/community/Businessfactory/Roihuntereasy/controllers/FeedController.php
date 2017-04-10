<?php

class Businessfactory_Roihuntereasy_FeedController extends Mage_Core_Controller_Front_Action
{
    public function feedAction()
    {
        Mage::log("Get product feed called.", null, 'feed.log');

        try {
            $format = $this->getRequest()->getParam("format");
            if (!isset($format) || trim($format)==='') {
                $format = "xml";
            }
            $file =  "feeds/roi_hunter_easy_feed_final." . $format;

            if (file_exists($file)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($file) .'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                readfile($file);
            }
            else {
                Mage::log("Product feed file does not exist.", null, 'feed.log');
                $this->getResponse()->setHttpResponseCode(404);
                $this->getResponse()->setBody(json_encode(
                    array('error_message' => 'Feed not found. Please look to log file for more information.')
                ));
            }
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, 'errors.log');
            Mage::log($exception, null, 'errors.log');
            Mage::log($this->getRequest(), null, 'errors.log');

            $this->getResponse()->setHttpResponseCode(500);
            $this->getResponse()->setBody(json_encode(
                array('error_message' => 'Cannot return feed. Please look to log file for more information.')
            ));
        }
    }
}

