<?php

class Businessfactory_Roihuntereasy_StoredetailsController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    protected function setResponseHeaders(& $response) {
        $response->setHeader("Content-type", "application/json");
        $response->setHeader("Access-Control-Allow-Origin", "*", true);
        $response->setHeader("Access-Control-Allow-Methods", "OPTIONS,GET,POST", true);
        $response->setHeader("Access-Control-Max-Age", "60", true);
        $response->setHeader("Access-Control-Allow-Headers", "X-Authorization", true);
    }

    /**
     * http://store.com/roihuntereasy/storedetails/check
     */
    public function checkAction()
    {
        Mage::log(__METHOD__ . "- Check called.");

        $response = $this->getResponse();
        $this->setResponseHeaders($response);

        $response->setBody(json_encode("rh-easy-active."));
    }

    /**
     * http://store.com/roihuntereasy/storedetails/debug
     */
    public function debugAction()
    {
        Mage::log(__METHOD__ . "- Debug called.");

        $request = $this->getRequest();
        $response = $this->getResponse();
        $this->setResponseHeaders($response);

        if ($request->getMethod() === "GET") {
            $this->processDebugGET();
        }
    }

    /**
     * GET
     * http://store.com/roihuntereasy/storedetails/debug
     *
     */
    function processDebugGET()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        try {
            // If table not empty, require authorization.
            $mainItemCollection = Mage::getModel("businessfactory_roihuntereasy/main")->getCollection();
            if ($mainItemCollection->count() > 0) {
                $authorizationHeader = $this->getRequest()->getHeader("X-Authorization");
                $dataEntity = $mainItemCollection->getLastItem();
                // If data exist check for client token.
                if ($dataEntity->getClientToken() != null && $dataEntity->getClientToken() !== $authorizationHeader) {
                    $response->setBody(json_encode("Not authorized"));
                    $response->setHttpResponseCode(403);
                    return;
                }
            }

            $resultData = $_SERVER;
            $resultData["Magento_Mode"] = Mage::getIsDeveloperMode() ? "developer" : "production";;
            $resultData["PHP_Version"] = phpversion();
            $resultData["Magento_Version"] = Mage::getVersion();
            $resultData["ROI_Hunter_Easy_Version"] = (string) Mage::getConfig()->getNode()->modules->Businessfactory_Roihuntereasy->version;

            $response->setBody(json_encode($resultData));
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, "errors.log");
            Mage::log($exception, null, "errors.log");
            Mage::log($request, null, "errors.log");
            $response->setHttpResponseCode(500);
        }
    }

    /**
     * http://store.com/roihuntereasy/storedetails/logs
     */
    public function logsAction()
    {
        Mage::log(__METHOD__ . "- Debug called.", "debug.log");

        $request = $this->getRequest();
        $response = $this->getResponse();
        $this->setResponseHeaders($response);

        if ($request->getMethod() === "GET") {
            $this->processLogsGET();
        }
    }

    /**
     * GET
     * http://store.com/roihuntereasy/storedetails/logs
     *
     */
    function processLogsGET()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        try {
            // If table not empty, require authorization.
            $mainItemCollection = Mage::getModel("businessfactory_roihuntereasy/main")->getCollection();
            if ($mainItemCollection->count() > 0) {
                $authorizationHeader = $this->getRequest()->getHeader("X-Authorization");
                $dataEntity = $mainItemCollection->getLastItem();
                // If data exist check for client token.
                if ($dataEntity->getClientToken() != null && $dataEntity->getClientToken() !== $authorizationHeader) {
                    $response->setBody(json_encode("Not authorized"));
                    $response->setHttpResponseCode(403);
                    return;
                }
            }

            $zipFilename = "all_logs.zip";
            $rootPath = Mage::getBaseDir("log");
            $zipAbsolutePath = $rootPath . "/" . $zipFilename;


            $zip = new ZipArchive();
            if ($zip->open($zipAbsolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                Mage::log("cannot open zip", null);
                $response->setBody("cannot open zip");
                return;
            }
            else {
                Mage::log("zip opened", null);
            }

            // Create recursive directory iterator
            /** @var SplFileInfo[] $files */
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath),
                RecursiveIteratorIterator::LEAVES_ONLY);

            foreach ($files as $name => $file) {
                // Skip directories (they would be added automatically)
                if (!$file->isDir() && strpos($file->getFilename(), "all_logs.zip") === false) {
                    // Get real and relative path for current file
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($rootPath));
                    // Add current file to archive
                    if ($zip->addFile($filePath, $relativePath) !== true) {
                        Mage::log("cannot add file to zip", null, "cro.log");
                        $response->setBody("cannot add file to zip");
                        return;
                    }
                    else {
                        Mage::log("added file to zip", null, "cro.log");
                    }
                }
            }
            // Zip archive will be created only after closing object
            $zip->close();

            header("Content-Description: File Transfer");
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename='" . basename($zipAbsolutePath) ."'");
            header("Expires: 0");
            header("Cache-Control: must-revalidate");
            header("Pragma: public");
            readfile($zipAbsolutePath);
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, "errors.log");
            Mage::log($exception, null, "errors.log");
            Mage::log($request, null, "errors.log");
            $response->setHttpResponseCode(500);
        }
    }

    /**
     * http://store.com/roihuntereasy/storedetails/state
     */
    public function stateAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $this->setResponseHeaders($response);

        if ($request->getMethod() === "GET") {
            $this->processStateGET();
        } else if ($request->getMethod() === "POST") {
            $this->processStatePOST();
        }
    }

    /**
     * GET
     * http://store.com/roihuntereasy/storedetails/state
     *
     * Returns state.
     */
    function processStateGET()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        try {
            $mainItemCollection = Mage::getModel("businessfactory_roihuntereasy/main")->getCollection();
            // If table empty, then create new item.
            if ($mainItemCollection->count() <= 0) {
                $response->setBody(json_encode("Entry not exist."));
                $response->setHttpResponseCode(404);
            } else {
                $response->setBody(json_encode($mainItemCollection->getLastItem()->getCreationState()));
            }
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, "errors.log");
            Mage::log($exception, null, "errors.log");
            Mage::log($request, null, "errors.log");
            $response->setHttpResponseCode(500);
        }
    }

    /**
     * POST
     * http://store.com/roihuntereasy/storedetails/state
     *
     * Updates state.
     */
    function processStatePOST()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        try {
            // Get request params
            $requestData = $request->getParams();
            $authorizationHeader = $request->getHeader("X-Authorization");
            $newCreationState = $request->getParam("new_state");

            if ($newCreationState === NULL) {
                $response->setBody(json_encode("Missing parameter."));
                $response->setHttpResponseCode(422);
                return;
            } else {
//             Prepare database item. If table empty, then create new item.
                $mainItemCollection = Mage::getModel("businessfactory_roihuntereasy/main")->getCollection();
                if ($mainItemCollection->count() <= 0) {
                    $dataEntity = Mage::getModel("businessfactory_roihuntereasy/main");
                    $dataEntity->setDescription("New");
                } else {
                    $dataEntity = Mage::getModel("businessfactory_roihuntereasy/main")->load($mainItemCollection->getLastItem()->getId());
                    $dataEntity->setDescription("Updated");

//                    If data already exist check for client token.
                    if ($dataEntity->getClientToken() !== NULL && $dataEntity->getClientToken() !== $authorizationHeader) {
                        $response->setBody(json_encode("Not authorized"));
                        $response->setHttpResponseCode(403);
                        return;
                    }
                }

                // Save data and send response success
                $dataEntity->setCreationState($newCreationState);
                $dataEntity->save();

                $response->setBody(json_encode(array(
                    "data" => $requestData
                )));
            }
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, "errors.log");
            Mage::log($exception, null, "errors.log");
            Mage::log($request, null, "errors.log");
            $response->setHttpResponseCode(500);
        }
    }

    /**
     * http://store.com/roihuntereasy/storedetails/add
     */
    public function addAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $this->setResponseHeaders($response);

        if ($request->getMethod() === "GET") {
            $this->processAddGET();
        } else if ($request->getMethod() === "POST") {
            $this->processAddPOST();
        }
    }

    /**
     * GET
     * http://store.com/roihuntereasy/storedetails/add
     *
     * Return dynamicAds table data.
     */
    function processAddGET()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        try {
            $mainItemCollection = Mage::getModel("businessfactory_roihuntereasy/main")->getCollection();
            $authorizationHeader = $this->getRequest()->getHeader("X-Authorization");

            // If table empty, then create new item.
            if ($mainItemCollection->count() <= 0) {
                $response->setBody(json_encode("Entry not exist."));
                $response->setHttpResponseCode(404);
            } else {
                $dataEntity = $mainItemCollection->getLastItem();
                if ($dataEntity->getClientToken() !== NULL && $dataEntity->getClientToken() !== $authorizationHeader) {
                    $response->setBody(json_encode("Not authorized"));
                    $response->setHttpResponseCode(403);
                    return;
                }

                $response->setBody(json_encode($dataEntity->getData()));
            }
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, "errors.log");
            Mage::log($exception, null, "errors.log");
            Mage::log($request, null, "errors.log");
            $response->setHttpResponseCode(500);
        }
    }

    /**
     * POST
     * http://store.com/roihuntereasy/storedetails/add
     *
     * Method should handle call after customer successful connection on goostav.
     */
    function processAddPOST()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        try {
            // Get request params
            $requestData = $request->getParams();
            Mage::log("Process add request with data: ", null, "debug.log");
            Mage::log(json_encode($requestData), null, "debug.log");

            $authorizationHeader = $request->getHeader("X-Authorization");

//             Prepare database item. If table empty, then create new item.
            $mainItemCollection = Mage::getModel("businessfactory_roihuntereasy/main")->getCollection();
            if ($mainItemCollection->count() <= 0) {
                $dataEntity = Mage::getModel("businessfactory_roihuntereasy/main");
                $dataEntity->setDescription("New");
            } else {
                $dataEntity = Mage::getModel("businessfactory_roihuntereasy/main")->load($mainItemCollection->getLastItem()->getId());
                $dataEntity->setDescription("Updated");

//                    If data already exist check for client token.
                if ($dataEntity->getClientToken() != NULL && $dataEntity->getClientToken() !== $authorizationHeader) {
                    $response->setBody(json_encode("Not authorized"));
                    $response->setHttpResponseCode(403);
                    return;
                }
            }

            // Save clientToken only if not exist
            if ($dataEntity->getClientToken() == NULL) {
                $client_token = $request->getParam("client_token");
                if ($client_token == NULL) {
                    $response->setBody(json_encode("Missing client token"));
                    $response->setHttpResponseCode(422);
                    return;
                } else {
                    $dataEntity->setClientToken($client_token);
                }
            }

            // Save AccessToken only if not exist
            if ($dataEntity->getAccessToken() == NULL) {
                $goostav_access_token = $request->getParam("access_token");
                if ($goostav_access_token == NULL) {
                    $response->setBody(json_encode("Missing tokens"));
                    $response->setHttpResponseCode(422);
                    return;
                } else {
                    $dataEntity->setAccessToken($goostav_access_token);
                }
            }

            // Save status and errors if something failed
            $status = $request->getParam("status");
            if ($status != NULL) $dataEntity->setStatus($status);
            $errors = $request->getParam("errors");
            if ($errors != NULL) $dataEntity->setErrors($errors);


            // Save customer id
            $customerId = $request->getParam("id");
            if ($customerId != NULL) $dataEntity->setCustomerId($customerId);
            // Save conversion id
            $conversionId = $request->getParam("conversion_id");
            if ($conversionId != NULL) $dataEntity->setConversionId($conversionId);

            // Set managed merchants
            $managedMerchants = $request->getParam("managed_merchants");
            if ($managedMerchants !== NULL) $dataEntity->setManagedMerchants($managedMerchants === "true");
            // Set adult content
            $adultOriented = $request->getParam("adult_oriented");
            if ($adultOriented !== NULL) $dataEntity->setAdultOriented($adultOriented === "true");
            // Set conversion label
            $conversionLabel = $request->getParam("conversion_label");
            if ($conversionLabel !== NULL) $dataEntity->setConversionLabel($conversionLabel);

            // Persist data
            $dataEntity->save();

            // Create verification file
            $filename = $request->getParam("site_verification_token");
            $this->createVerificationFile($filename);

            // Return response
            $response->setBody(json_encode(array(
                "data" => $requestData
            )));
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, "errors.log");
            Mage::log($exception, null, "errors.log");
            Mage::log($request, null, "errors.log");
            $response->setHttpResponseCode(500);
        }
    }

    /**
     * Create verification file.
     */
    function createVerificationFile($filename)
    {
        try {
            if ($filename != NULL) {
                $content = "google-site-verification: " . $filename;

                // Apache
                $io = new Varien_Io_File();
                $io->setAllowCreateFolders(true);
                $io->open(array("path" => Mage::getBaseDir()));

                if ($io->fileExists($filename)) {
                    $io->rm($filename);
                }
                $io->streamOpen($filename);
                $io->streamWrite($content);
                $io->streamClose();

                $io->close();
                // Nginx - check if it is necessary to create file in public folder

            } else {
                Mage::log("ERROR: Cannot create verification file. Missing filename", null, "errors.log");
            }
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, "errors.log");
            Mage::log($exception, null, "errors.log");
        }
    }
}
