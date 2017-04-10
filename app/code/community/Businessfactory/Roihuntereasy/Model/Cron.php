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
        Mage::log(__METHOD__ . ": scheduled feed generation. ", null, 'cron.log');

        $jobsRoot = Mage::getConfig()->getNode('crontab/jobs');
        $jobConfig = $jobsRoot->{$schedule->getJobCode()};
        $fileFormat = (string) $jobConfig->format;
        $this->createFeed($fileFormat);
    }

    /**
     * Method start new feed creation process, if not another feed creation process running.
     */
    public function createFeed($fileFormat)
    {
        Mage::log($fileFormat . ": "  . __METHOD__ . " cron", null, 'cron.log');
        $filename = "businessFactoryRoiHunterEasyFeedSign" . $fileFormat;
        $io = new Varien_Io_File();

        try {
            $io->setAllowCreateFolders(true);
            $io->open(array('path' => Mage::getBaseDir()));

            if ($io->fileExists($filename)) {
                Mage::log($fileFormat. ": Feed generation already running.", null, 'cron.log');
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
            Mage::log($fileFormat . ": "  . __METHOD__ . " exception.", null, 'errors.log');
            Mage::log($exception->getMessage(), null, 'errors.log');

            // Try delete file also when exception occurred.
            try {
                $io->rm($filename);

                $io->close();
            } catch (Exception $exception) {
                Mage::log($fileFormat . ": "  . __METHOD__ . " exception.", null, 'errors.log');
                Mage::log($exception->getMessage(), null, 'errors.log');
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
        $io->open(array('path' => Mage::getBaseDir() . "/feeds"));

        // remove the temporary file if exists
        if ($io->fileExists($pathTemp)) {
            $io->rm($pathTemp);
        }
        $io->streamOpen($pathTemp);

        // get collection of all products
        $products = $this->getProductCollection();

        // measurement variables
        $total_time_start = microtime(true);
        $time_start = microtime(true);
        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);
        Mage::log('getProductCollection count: ' . count($products) . '. Execution time: ' . $execution_time, null, 'cron.log');

        try {
            if ($fileFormat === "xml") {
                $this->generateAndSaveFeedXML($products, $io);
            }
            else if ($fileFormat === "csv") {
                $this->generateAndSaveFeedCSV($products, $io);
            }

            $io->streamClose();

            if ($io->mv($pathTemp, $pathFinal)) {
                Mage::log("Created feed renamed successful", null, 'cron.log');
            } else {
                Mage::log("ERROR: Created feed renamed unsuccessful", null, 'cron.log');
            }

            $total_time_end = microtime(true);
            $total_execution_time = ($total_time_end - $total_time_start);
            Mage::log('total execution time: ' . $total_execution_time, null, 'cron.log');
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
            'ID',
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
                case 'downloadable':
                    if ($_product->getPrice() <= 0) {
                        break;
                    }
//              Else same processing as simple product
                case 'simple':
                    $productDict = $this->get_simple_product_dict($_product);
                    $io->streamWriteCsv($productDict);
                    break;
                case 'configurable':
                    $productDictArray = $this->get_configurable_product_dict($_product);
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
    private function get_configurable_product_dict($_product)
    {
        $conf = Mage::getModel('catalog/product_type_configurable')->setProduct($_product);
        $simple_collection = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();

        $productArray = array();

        foreach ($simple_collection as $_childproduct) {
            $productDict = array(
                "ID" => $this->get_id($_product, $_childproduct),
                "Item title" => $this->get_title($_product),
                "Final URL" => $this->get_product_url($_product),
                "Image URL" => $this->get_image_url($_childproduct),
                "Item description" => $this->get_description($_product),
                "Price" => $this->get_price($_childproduct, true),
                "Sale price" => $this->get_sale_price($_childproduct, true),
            );
            array_push($productArray, $productDict);
        }
        return $productArray;
    }

    /**
     * @param $_product
     * @param XMLWriter $xmlWriter
     */
    private function get_simple_product_dict($_product)
    {
        $productDict = array(
            "ID" => $this->get_id($_product, null),
            "Item title" => $this->get_title($_product),
            "Final URL" => $this->get_product_url($_product),
            "Image URL" => $this->get_image_url($_product),
            "Item description" => $this->get_description($_product),
            "Price" => $this->get_price($_product, true),
            "Sale price" => $this->get_sale_price($_product, true),
        );
        return $productDict;
    }

    /**
     * Feed generation function
     */
    private function generateAndSaveFeedXML($products, $io) {
        // debug variables
        $limit_enabled = false;
        $simple_products_count = 0;
        $configurable_products_count = 0;
        $simple_products_limit = 2;
        $configurable_products_limit = 1;

        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->startDocument('1.0', 'UTF-8');
        $xmlWriter->setIndent(true);

        $xmlWriter->startElement('rss');
        $xmlWriter->writeAttribute('version', '2.0');
        $xmlWriter->writeAttributeNs('xmlns', 'g', null, 'http://base.google.com/ns/1.0');
        $xmlWriter->startElement('channel');
        $xmlWriter->writeElement('title', 'ROI Hunter Easy - Magento data feed');
        $xmlWriter->writeElement('description', 'Magento data feed used in Google Merchants');
        $xmlWriter->writeElement('link', Mage::app()->getStore()->getBaseUrl());

        $this->count = 0;
        foreach ($products as $_product) {

            switch ($_product->getTypeId()) {
                case 'downloadable':
                    if ($_product->getPrice() <= 0) {
                        break;
                    }
//                        Else same processing as simple product
                case 'simple':
                    if (!$limit_enabled || $simple_products_count < $simple_products_limit) {
                        $this->write_simple_product_xml($_product, $xmlWriter);
                        $simple_products_count++;
                    }
                    break;
                case 'configurable':
                    if (!$limit_enabled || $configurable_products_count < $configurable_products_limit) {
                        $this->write_configurable_product_xml($_product, $xmlWriter);
                        $configurable_products_count++;
                    }
                    break;
            }
            if ($limit_enabled && $simple_products_count >= $simple_products_limit && $configurable_products_count >= $configurable_products_limit) {
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
        $collection = Mage::getModel('catalog/product')->getCollection();

        // select necessary attributes
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('short_description');
        $collection->addAttributeToSelect('description');
        $collection->addAttributeToSelect('price');
        $collection->addAttributeToSelect('special_price');
        $collection->addAttributeToSelect('size');
        $collection->addAttributeToSelect('color');
        $collection->addAttributeToSelect('pattern');
        $collection->addAttributeToSelect('image');

        // Allow only visible products
        $visibility = array(
            Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
            Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
        );
        $collection->addAttributeToFilter('visibility', $visibility);
        $collection->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);

        // setting correct Product URL
        $collection->addUrlRewrite();
        $storeId = Mage::app()
            ->getDefaultStoreView()
            ->getStoreId();
        $collection->setStoreId($storeId);
        Mage::app()->setCurrentStore($storeId);

        $collection->load();

        Mage::log("Default store ID: " . $storeId, null, 'cron.log');

        return $collection;
    }

    /**
     * @param Mixed $_product
     * @param XMLWriter $xmlWriter
     */
    private function write_configurable_product_xml($_product, $xmlWriter)
    {
        $catCollection = $this->get_product_types($_product);

        $conf = Mage::getModel('catalog/product_type_configurable')->setProduct($_product);
        $simple_collection = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();

        foreach ($simple_collection as $_childproduct) {
            $xmlWriter->startElement('item');

            // ID belongs to the child product's ID to make this product unique
            $xmlWriter->writeElement('g:id', $this->get_id($_product, $_childproduct));
            $xmlWriter->writeElement('g:item_group_id', $this->get_item_group_id($_product, $_childproduct));
            $xmlWriter->writeElement('g:display_ads_id', $this->get_display_ads_id($_product, $_childproduct));

            // process common attributes
            $this->write_parent_product_attributes_xml($_product, $xmlWriter);
            // process advanced attributes
            $this->write_child_product_attributes_xml($_childproduct, $xmlWriter);
            // categories
            $this->write_product_types_xml($catCollection, $xmlWriter);

            $xmlWriter->endElement();
        }
    }

    /**
     * @param $_product
     * @param XMLWriter $xmlWriter
     */
    private function write_simple_product_xml($_product, $xmlWriter)
    {
        $xmlWriter->startElement('item');

        // ID belongs to the simple product's SKU
        $xmlWriter->writeElement('g:id',  $this->get_id($_product, null));
        $xmlWriter->writeElement('g:display_ads_id',  $this->get_display_ads_id($_product, null));

        // process common attributes
        $this->write_parent_product_attributes_xml($_product, $xmlWriter);
        // process advanced attributes
        $this->write_child_product_attributes_xml($_product, $xmlWriter);
        // categories
        $catCollection = $this->get_product_types($_product);
        $this->write_product_types_xml($catCollection, $xmlWriter);

        $xmlWriter->endElement();
    }

    /**
     * @param Mixed $_product
     * @param XMLWriter $xmlWriter
     */
    function write_parent_product_attributes_xml($_product, $xmlWriter)
    {
        $xmlWriter->writeElement('g:title', $this->get_title($_product));
        $xmlWriter->writeElement('g:description', $this->get_description($_product));
        // Product URL
        // $_product->getData('request_path') can return product handle like - aviator-sunglasses.html
        $xmlWriter->writeElement('g:link', $this->get_product_url($_product));

        // replaced getAttributeText with safer option
        $attributeCode = 'manufacturer';
        if ($_product->getData($attributeCode) !== null){
            $xmlWriter->writeElement('g:brand', $_product->getAttributeText($attributeCode));
        }

        $xmlWriter->writeElement('g:condition', 'new');
    }

    /**
     * @param Mixed $_product
     * @param XMLWriter $xmlWriter
     */
    function write_child_product_attributes_xml($_product, $xmlWriter)
    {
        $xmlWriter->writeElement('g:image_link', $this->get_image_url($_product));

//        $this->_logger->debug('gtin: ' . $_product->getEan());
        $xmlWriter->writeElement('g:mpn', $_product->getSku());
        if (strlen($_product->getEan()) > 7) {
            $xmlWriter->writeElement('g:gtin', $_product->getEan());
        }

        $xmlWriter->writeElement('g:price', $this->get_price($_product));
        $xmlWriter->writeElement('g:sale_price', $this->get_sale_price($_product));
        // replaced getAttributeText with safer option
        $attributeCode = 'color';
        if ($_product->getData($attributeCode) !== null){
            $xmlWriter->writeElement('g:color', $_product->getAttributeText($attributeCode));
        }
        // replaced getAttributeText with safer option
        $attributeCode = 'size';
        if ($_product->getData($attributeCode) !== null){
            $xmlWriter->writeElement('g:size', $_product->getAttributeText($attributeCode));
        }

        $xmlWriter->writeElement('g:availability', $this->do_isinstock($_product));
    }

    /**
     * @param Mixed $catCollection
     * @param XMLWriter $xmlWriter
     */
    function write_product_types_xml($catCollection, $xmlWriter)
    {
        /** @var Mixed $category */
        foreach ($catCollection as $category) {
            $xmlWriter->writeElement('g:product_type', $category->getName());
        }
    }

    /**
     * @param Mixed $_product
     * @return string
     */
    function do_isinstock($_product)
    {
        $stockItem = $_product->getStockItem();
        if($stockItem->getIsInStock())
        {
            $stockval = 'in stock';
        }
        else
        {
            $stockval = 'out of stock';
        }

        return $stockval;
    }

    /**
     * @param Mixed $_product
     * @return mixed
     */
    function get_product_types($_product)
    {
        // SELECT name FROM category
        // if I want to load more attributes, I need to select them first
        // loading and selecting is processor intensive! Selecting more attributes will result in longer delay!
        return $_product->getCategoryCollection()->addAttributeToSelect('name')->load();
    }

    /**
     * @param Mixed $product
     * @return string price
     */
    function get_product_url($product)
    {
        return $product->getUrlInStore();
    }

    /**
     * @param Mixed $product
     * @return string price
     */
    function get_currency()
    {
        return $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
    }

    /**
     * @param Mixed $product
     * @return string price
     */
    function get_sale_price($product, $withCurrency=false)
    {
        $price =  $product->getSpecialPrice();

        if ($price && $withCurrency) {
            $price = $price." ".$this->get_currency();
        }

        return $price;
    }

    /**
     * @param Mixed $product
     * @return string price
     */
    function get_price($product, $withCurrency=false)
    {
        $price = $product->getPrice();

        if ($withCurrency) {
            $price = $price." ".$this->get_currency();
        }

        return $price;
    }

    /**
     * @param Mixed $product
     * @return string item_group_id
     */
    function get_item_group_id($product)
    {
        return "mag_".$product->getId();
    }

    /**
     * @param Mixed $product
     * @return string display_ads_id
     */
    function get_display_ads_id($product, $childproduct=null)
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
    function get_id($product, $childproduct=null)
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
    function get_title($product)
    {
        return $product->getName();
    }

    /**
     * @param Mixed $product
     * @return string description
     */
    function get_description($product)
    {
        $description = $product->getShortDescription();
        if (!$description) {
            $description = $product->getDescription();
        }
        return ($description);
    }

    /**
     * @param Mixed $product
     * @return string image URL
     */
    function get_image_url($product)
    {
        $productMediaConfig = Mage::getModel('catalog/product_media_config');
        $baseImageUrl = $productMediaConfig->getMediaUrl($product->getImage());

        return $baseImageUrl;
    }
}
