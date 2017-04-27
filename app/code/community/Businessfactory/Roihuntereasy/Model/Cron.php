<?php

class Businessfactory_Roihuntereasy_Model_Cron extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
    }

    /**
     * Method start new feed creation process from cron schedule.
     */
    public function scheduleFeed($schedule) {
        Mage::log(__METHOD__ . ": scheduled feed generation. ", null, "cron.log");

        $jobsRoot = Mage::getConfig()->getNode("crontab/jobs");
        $jobConfig = $jobsRoot->{$schedule->getJobCode()};
        $fileFormat = (string) $jobConfig->format;
        $this->createFeed($fileFormat);
    }

    /**
     * Method start new feed creation process, if not another feed creation process running.
     */
    public function createFeed($fileFormat="xml")
    {
        Mage::log($fileFormat . ": "  . __METHOD__ . " cron", null, "cron.log");
        $filename = "businessFactoryRoiHunterEasyFeedSign" . $fileFormat;
        $io = new Varien_Io_File();

        try {
            $io->setAllowCreateFolders(true);
            $io->open(array("path" => Mage::getBaseDir()));

            if ($io->fileExists($filename)) {
                Mage::log($fileFormat. ": Feed generation already running.", null, "cron.log");
                return false;
            }

            $io->streamOpen($filename);
            $io->streamWrite("Running");
            $io->streamClose();

            // Generate feed
            $this->generateAndSaveFeed($fileFormat);

            // Delete file
            $io->rm($filename);

            $io->close();

            return true;
        } catch (Exception $exception) {
            Mage::log($fileFormat . ": "  . __METHOD__ . " exception.", null, "errors.log");
            Mage::log($exception->getMessage(), null, "errors.log");

            // Try delete file also when exception occurred.
            try {
                $io->rm($filename);

                $io->close();
            } catch (Exception $exception) {
                Mage::log($fileFormat . ": "  . __METHOD__ . " exception.", null, "errors.log");
                Mage::log($exception->getMessage(), null, "errors.log");
            }
            return false;
        }
    }

    /**
     * Feed generation function
     */
    private function generateAndSaveFeed($fileFormat)
    {
        // create tmp file for writing blocks of the products
        $pathTemp = "roi_hunter_easy_feed_temp." . $fileFormat;
        // set the name of the final file (tmp is renamed to this named after the operation is completed)
        $pathFinal = "roi_hunter_easy_feed_final." . $fileFormat;

        // create IO Stream for writing
        $io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);
        $io->open(array("path" => Mage::getBaseDir() . "/feeds"));

        // remove the temporary file if exists
        if ($io->fileExists($pathTemp)) {
            $io->rm($pathTemp);
        }
        $io->streamOpen($pathTemp);

        // get collection of all products
        $products = $this->getProductCollection();

        // measurement variables
        $totalTimeStart = microtime(true);
        $timeStart = microtime(true);
        $timeEnd = microtime(true);
        $executionTime = ($timeEnd - $timeStart);
        Mage::log("getProductCollection count: " . count($products) . ". Execution time: " . $executionTime, null, "cron.log");

        try {
            if ($fileFormat === "xml") {
                $this->generateAndSaveFeedXML($products, $io);
            }
            else if ($fileFormat === "csv") {
                $this->generateAndSaveFeedCSV($products, $io);
            }

            $io->streamClose();

            if ($io->mv($pathTemp, $pathFinal)) {
                Mage::log("Created feed renamed successful", null, "cron.log");
            } else {
                Mage::log("ERROR: Created feed renamed unsuccessful", null, "cron.log");
            }

            $totalTimeEnd = microtime(true);
            $totalExecutionTime = ($totalTimeEnd - $totalTimeStart);
            Mage::log("total execution time: " . $totalExecutionTime, null, "cron.log");
        } catch (Exception $e) {
            Mage::throwException($e);
        }

        $io->close();

        return true;
    }

    /**
     * Feed generation function
     */
    private function generateAndSaveFeedCSV($products, $io) {
        // CSV headers
        $csvHeader = array(
            "ID",
            "Item title",
            "Final URL",
            "Image URL",
            "Item description",
            "Price",
            "Sale price"
        );

        // write headers to CSV file
        $io->streamWriteCsv($csvHeader);

        $this->count = 0;
        foreach ($products as $_product) {
            switch ($_product->getTypeId()) {
                case "downloadable":
                    if ($this->getPrice($_product) <= 0) {
                        break;
                    }
//              Else same processing as simple product
                case "simple":
                    $productDict = $this->getSimpleProductDict($_product);
                    $io->streamWriteCsv($productDict);
                    break;
                case "configurable":
                    $productDictArray = $this->getConfigurableProductDict($_product);
                    foreach ($productDictArray as $productDict){
                        $io->streamWriteCsv($productDict);
                    }
                    break;
            }
        }

    }

    /**
     * @param Mixed $_product
     * @param XMLWriter $xmlWriter
     */
    private function getConfigurableProductDict($_product)
    {
        $conf = Mage::getModel("catalog/product_type_configurable")->setProduct($_product);
        $simpleCollection = $conf->getUsedProductCollection()->addAttributeToSelect("*")->addFilterByRequiredOptions();

        $productArray = array();

        foreach ($simpleCollection as $_childproduct) {
            $productDict = array(
                "ID" => $this->getId($_product, $_childproduct),
                "Item title" => $this->getTitle($_product),
                "Final URL" => $this->getProductUrl($_product),
                "Image URL" => $this->getImageUrl($_product),
                "Item description" => $this->getDescription($_product),
                "Price" => $this->getPrice($_product, true),
                "Sale price" => $this->getSalePrice($_product, true),
            );
            array_push($productArray, $productDict);
        }
        return $productArray;
    }

    /**
     * @param $_product
     * @param XMLWriter $xmlWriter
     */
    private function getSimpleProductDict($_product)
    {
        $productDict = array(
            "ID" => $this->getId($_product, null),
            "Item title" => $this->getTitle($_product),
            "Final URL" => $this->getProductUrl($_product),
            "Image URL" => $this->getImageUrl($_product),
            "Item description" => $this->getDescription($_product),
            "Price" => $this->getPrice($_product, true),
            "Sale price" => $this->getSalePrice($_product, true),
        );
        return $productDict;
    }

    /**
     * Feed generation function
     */
    private function generateAndSaveFeedXML($products, $io) {
        // debug variables
        $limitEnabled = false;
        $simpleProductsCount = 0;
        $configurableProductsCount = 0;
        $simpleProductsLimit = 1;
        $configurableProductsLimit = 0;

        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->startDocument("1.0", "UTF-8");
        $xmlWriter->setIndent(true);

        $xmlWriter->startElement("rss");
        $xmlWriter->writeAttribute("version", "2.0");
        $xmlWriter->writeAttributeNs("xmlns", "g", null, "http://base.google.com/ns/1.0");
        $xmlWriter->startElement("channel");
        $xmlWriter->writeElement("title", "ROI Hunter Easy - Magento data feed");
        $xmlWriter->writeElement("description", "Magento data feed used in Google Merchants");
        $xmlWriter->writeElement("link", Mage::app()->getStore()->getBaseUrl());
        $xmlWriter->writeElement('date', Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'));

        $this->count = 0;
        foreach ($products as $_product) {

            switch ($_product->getTypeId()) {
                case "downloadable":
                    if ($_product->getPrice() <= 0) {
                        break;
                    }
//                        Else same processing as simple product
                case "simple":
                    if (!$limitEnabled || $simpleProductsCount < $simpleProductsLimit) {
                        $this->writeSimpleProductXML($_product, $xmlWriter);
                        $simpleProductsCount++;
                    }
                    break;
                case "configurable":
                    if (!$limitEnabled || $configurableProductsCount < $configurableProductsLimit) {
                        $this->writeConfigurableProductXML($_product, $xmlWriter);
                        $configurableProductsCount++;
                    }
                    break;
            }
            if ($limitEnabled && $simpleProductsCount >= $simpleProductsLimit && $configurableProductsCount >= $configurableProductsLimit) {
                break;
            }

            $this->count++;
            if ($this->count >= 512) {
                $this->count = 0;
                // After each 512 products flush memory to file.
                $io->streamWrite($xmlWriter->flush());
            }
        }

        $xmlWriter->endElement();
        $xmlWriter->endElement();
        $xmlWriter->endDocument();

        // Final memory flush, rename temporary file and feed is done.
        $io->streamWrite($xmlWriter->flush());
    }

    public function getProductCollection()
    {
        $collection = Mage::getModel("catalog/product")->getCollection();

        // select necessary attributes
        $collection->addAttributeToSelect("name");
        $collection->addAttributeToSelect("short_description");
        $collection->addAttributeToSelect("description");
        $collection->addAttributeToSelect("price");
        $collection->addAttributeToSelect("final_price");
        $collection->addAttributeToSelect("minimal_price");
        $collection->addAttributeToSelect("special_price");
        $collection->addAttributeToSelect("size");
        $collection->addAttributeToSelect("color");
        $collection->addAttributeToSelect("pattern");
        $collection->addAttributeToSelect("image");

        // Allow only visible products
        $visibility = array(
            Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
            Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
        );
        $collection->addAttributeToFilter("visibility", $visibility);
        $collection->addAttributeToFilter("status", Mage_Catalog_Model_Product_Status::STATUS_ENABLED);

        // setting correct Product URL
        $collection->addUrlRewrite();
        $storeId = Mage::app()
            ->getDefaultStoreView()
            ->getStoreId();
        $collection->setStoreId($storeId);
        // adding website filter removes products unavailable in the store on the frontend
        $collection->addStoreFilter($storeId);
        Mage::app()->setCurrentStore($storeId);

        $collection->load();

        Mage::log("Default store ID: " . $storeId, null, "cron.log");

        return $collection;
    }

    /**
     * @param Mixed $_product
     * @param XMLWriter $xmlWriter
     */
    private function writeConfigurableProductXML($_product, $xmlWriter)
    {
        $catCollection = $this->getProductTypes($_product);

        $conf = Mage::getModel("catalog/product_type_configurable")->setProduct($_product);
        $simpleCollection = $conf->getUsedProductCollection()->addAttributeToSelect("*")->addFilterByRequiredOptions();

        foreach ($simpleCollection as $_childproduct) {
            $xmlWriter->startElement("item");

            // ID belongs to the child product"s ID to make this product unique
            $xmlWriter->writeElement("g:id", $this->getId($_product, $_childproduct));
            $xmlWriter->writeElement("g:item_group_id", $this->getItemGroupId($_product));
            $xmlWriter->writeElement("g:display_ads_id", $this->getDisplayAdsId($_product, $_childproduct));

            // process common attributes
            $this->writeParentProductAttributesXML($_product, $xmlWriter);
            // process advanced attributes
            $this->writeChildProductAttributesXML($_childproduct, $xmlWriter);
            // categories
            $this->writeProductTypesXml($catCollection, $xmlWriter);

            $xmlWriter->endElement();
        }
    }

    /**
     * @param $_product
     * @param XMLWriter $xmlWriter
     */
    private function writeSimpleProductXML($_product, $xmlWriter)
    {
        $xmlWriter->startElement("item");

        // ID belongs to the simple product"s SKU
        $xmlWriter->writeElement("g:id",  $this->getId($_product, null));
        $xmlWriter->writeElement("g:display_ads_id",  $this->getDisplayAdsId($_product, null));

        // process common attributes
        $this->writeParentProductAttributesXML($_product, $xmlWriter);
        // process advanced attributes
        $this->writeChildProductAttributesXML($_product, $xmlWriter);
        // categories
        $catCollection = $this->getProductTypes($_product);
        $this->writeProductTypesXml($catCollection, $xmlWriter);

        $xmlWriter->endElement();
    }

    /**
     * @param Mixed $_product
     * @param XMLWriter $xmlWriter
     */
    function writeParentProductAttributesXML($_product, $xmlWriter)
    {
        $xmlWriter->writeElement("g:title", $this->getTitle($_product));
        $xmlWriter->writeElement("g:description", $this->getDescription($_product));
        // Product URL
        // $_product->getData("request_path") can return product handle like - aviator-sunglasses.html
        $xmlWriter->writeElement("g:link", $this->getProductUrl($_product));

        // replaced getAttributeText with safer option
        $attributeCode = "manufacturer";
        if ($_product->getData($attributeCode) !== null){
            $xmlWriter->writeElement("g:brand", $_product->getAttributeText($attributeCode));
        }

        $xmlWriter->writeElement("g:condition", "new");
        // get sale price from the parent product in case that the special price is set on the configurable product
        // but not on the children
        // TODO: possible improvement: check children simple product first
        $xmlWriter->writeElement("g:price", $this->getPrice($_product));
        $xmlWriter->writeElement("g:sale_price", $this->getSalePrice($_product));
        // get image URL from the parent product in case that the image is set on the configurable product
        // but not on the children simple product
        // TODO: possible improvement: check children simple product first
        $xmlWriter->writeElement("g:image_link", $this->getImageUrl($_product));
    }

    /**
     * @param Mixed $_product
     * @param XMLWriter $xmlWriter
     */
    function writeChildProductAttributesXML($_product, $xmlWriter)
    {
//        $this->_logger->debug("gtin: " . $_product->getEan());
        $xmlWriter->writeElement("g:mpn", $_product->getSku());
        if (strlen($_product->getEan()) > 7) {
            $xmlWriter->writeElement("g:gtin", $_product->getEan());
        }

        // replaced getAttributeText with safer option
        $attributeCode = "color";
        if ($_product->getData($attributeCode) !== null){
            $xmlWriter->writeElement("g:color", $_product->getAttributeText($attributeCode));
        }
        // replaced getAttributeText with safer option
        $attributeCode = "size";
        if ($_product->getData($attributeCode) !== null){
            $xmlWriter->writeElement("g:size", $_product->getAttributeText($attributeCode));
        }

        $xmlWriter->writeElement("g:availability", $this->doIsInStock($_product));
    }

    /**
     * @param Mixed $catCollection
     * @param XMLWriter $xmlWriter
     */
    function writeProductTypesXML($catCollection, $xmlWriter)
    {
        /** @var Mixed $category */
        foreach ($catCollection as $category) {
            $xmlWriter->writeElement("g:product_type", $category->getName());
        }
    }

    /**
     * @param Mixed $_product
     * @return string
     */
    function doIsInStock($_product)
    {
        $stockItem = $_product->getStockItem();
        if($stockItem->getIsInStock())
        {
            $stockval = "in stock";
        }
        else
        {
            $stockval = "out of stock";
        }

        return $stockval;
    }

    /**
     * @param Mixed $_product
     * @return mixed
     */
    function getProductTypes($_product)
    {
        // SELECT name FROM category
        // if I want to load more attributes, I need to select them first
        // loading and selecting is processor intensive! Selecting more attributes will result in longer delay!
        return $_product->getCategoryCollection()->addAttributeToSelect("name")->load();
    }

    /**
     * @param Mixed $product
     * @return string price
     */
    function getProductUrl($product)
    {
        return $product->getUrlInStore();
    }

    /**
     * @param Mixed $product
     * @return string price
     */
    function getCurrency()
    {
        return $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
    }

    /**
     * @param Mixed $product
     * @return string price
     */
    function getSalePrice($product, $withCurrency=false)
    {
        $price = $product->getFinalPrice();

        if ($price && $withCurrency) {
            $price = $price." ".$this->getCurrency();
        }

        return $price;
    }

    /**
     * @param Mixed $product
     * @return string price
     */
    function getPrice($product, $withCurrency=false)
    {
        $price = $product->getPrice();

        if ($withCurrency) {
            $price = $price." ".$this->getCurrency();
        }

        return $price;
    }

    /**
     * @param Mixed $product
     * @return string item_group_id
     */
    function getItemGroupId($product)
    {
        return "mag_".$product->getId();
    }

    /**
     * @param Mixed $product
     * @return string display_ads_id
     */
    function getDisplayAdsId($product, $childproduct=null)
    {
        if ($childproduct) {
            return "mag_".$product->getId()."_".$childproduct->getId();
        }
        else {
            return "mag_".$product->getId();
        }
    }

    /**
     * @param Mixed $product
     * @return string id
     */
    function getId($product, $childproduct=null)
    {
        if ($childproduct) {
            return "mag_".$product->getId()."_".$childproduct->getId();
        }
        else {
            return "mag_".$product->getId();
        }
    }

    /**
     * @param Mixed $product
     * @return string title
     */
    function getTitle($product)
    {
        return $product->getName();
    }

    /**
     * @param Mixed $product
     * @return string description
     */
    function getDescription($product)
    {
        $description = $product->getShortDescription();
        if (!$description) {
            $description = $product->getDescription();
        }
        return $description;
    }

    /**
     * @param Mixed $product
     * @return string image URL
     */
    function getImageUrl($product)
    {
        $imageUrl = null;

        // try to retrieve base image URL
        if ($product->getImage() != "no_selection" && $product->getImage()){
            $productMediaConfig = Mage::getModel("catalog/product_media_config");
            $imageUrl = $productMediaConfig->getMediaUrl($product->getImage());
        }

        return $imageUrl;
    }
}
