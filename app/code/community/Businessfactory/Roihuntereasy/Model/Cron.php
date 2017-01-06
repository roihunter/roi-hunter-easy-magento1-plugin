<?php

class Businessfactory_Roihuntereasy_Model_Cron extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
    }

    public function test() {
        Mage::log("Cron schedule running.", null, "cron.log");
    }

    /**
     * Method start new feed creation process, if not another feed creation process running.
     */
    public function createFeed()
    {
        Mage::log(__METHOD__ . " cron", null, 'cron.log');
        $filename = "businessFactoryRoiHunterEasyFeedSign";

        try {
            $io = new Varien_Io_File();
            $io->setAllowCreateFolders(true);
            $io->open(array('path' => Mage::getBaseDir()));

            if ($io->fileExists($filename)) {
                Mage::log("Feed generation already running.", null, 'cron.log');
                return false;
            }

            $io->streamOpen($filename);
            $io->streamWrite("Running");
            $io->streamClose();

            // Generate feed
            $this->generateAndSaveFeed();

            // Delete file
            $io->rm($filename);

            return true;
        } catch (Exception $exception) {
            Mage::log(__METHOD__ . " exception.", null, 'errors.log');
            Mage::log($exception, null, 'errors.log');

            // Try delete file also when exception occurred.
            try {
                $io->rm($filename);
            } catch (Exception $exception) {
                Mage::log(__METHOD__ . " exception.", null, 'errors.log');
                Mage::log($exception, null, 'errors.log');
            }
            return false;
        }
    }

    /**
     * Feed generation function
     */
    private function generateAndSaveFeed()
    {
        $pathTemp = "roi_hunter_easy_feed_temp.xml";
        $pathFinal = "roi_hunter_easy_feed_final.xml";

        $io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);
        $io->open(array('path' => Mage::getBaseDir() . "/feeds"));

        // Clear file
        if ($io->fileExists($pathTemp)) {
            $io->rm($pathTemp);
        }
        $io->streamOpen($pathTemp);

        try {
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

            $total_time_start = microtime(true);
            $time_start = microtime(true);
            $products = $this->getProductCollection();
            $time_end = microtime(true);
            $execution_time = ($time_end - $time_start);
            Mage::log('getProductCollection count: ' . count($products) . '. Execution time: ' . $execution_time, null, 'cron.log');

            $this->count = 0;

            // debug variables
            $limit_enabled = false;
            $simple_products_count = 0;
            $configurable_products_count = 0;
            $simple_products_limit = 2;
            $configurable_products_limit = 1;

            foreach ($products as $_product) {

                $xmlWriter->writeElement('store', $_product->getStoreId());
//                $_product->setStoreId(Mage::app()->getStore());

                switch ($_product->getTypeId()) {
                    case 'downloadable':
                        if ($_product->getPrice() <= 0) {
//                            $this->_logger->info("Skip this");
                            break;
                        }
//                        Else same processing as simple product
                    case 'simple':
                        if (!$limit_enabled || $simple_products_count < $simple_products_limit) {
                            $this->write_simple_product($_product, $xmlWriter);
                            $simple_products_count++;
                        }
                        break;
                    case 'configurable':
                        if (!$limit_enabled || $configurable_products_count < $configurable_products_limit) {
                            $this->write_configurable_product($_product, $xmlWriter);
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
        return true;
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

        $collection->addUrlRewrite();

        $collection->load();

        return $collection;
    }

    /**
     * @param $_product
     * @param XMLWriter $xmlWriter
     */
    private function write_simple_product($_product, $xmlWriter)
    {
        $xmlWriter->startElement('item');

        // process common attributes
        $this->write_parent_product_attributes($_product, $xmlWriter);
        // process advanced attributes
        $this->write_child_product_attributes($_product, $xmlWriter);
        // categories
        $catCollection = $this->get_product_types($_product);
        $this->write_product_types($catCollection, $xmlWriter);

        $xmlWriter->endElement();
    }

    /**
     * @param Mixed $_product
     * @param XMLWriter $xmlWriter
     */
    function write_parent_product_attributes($_product, $xmlWriter)
    {
        $xmlWriter->writeElement('g:title', $_product->getName());
        $xmlWriter->writeElement('g:description', $this->get_description($_product));
        $xmlWriter->writeElement('g:link', $_product->getProductUrl());
        $xmlWriter->writeElement('g:brand', $_product->getAttributeText('manufacturer'));

        $xmlWriter->writeElement('g:condition', 'new');
        // TODO add more attributes if needed.
//        $xmlWriter->writeElement('g:size_system', 'uk');
//        $xmlWriter->writeElement('g:age_group', 'adult');

//        $xmlWriter->writeElement('g:identifier_exists', 'TRUE');
//        $xmlWriter->writeElement('g:adult', $this->do_is_adult($_product));
    }

//    /**
//     * @param Mixed $_product
//     * @return string
//     */
//    function do_is_adult($_product)
//    {
//        // TODO add decision if needed.
////        switch ($_product->getAttributeText('familysafe')) {
////            case 'No':
////                $isadult = "FALSE";
////            default:
////                $isadult = "TRUE";
////        }
//        return ("FALSE");
//    }

    /**
     * @param Mixed $product
     * @return mixed
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
     * @return string
     */
    function get_image_url($product)
    {
        $productMediaConfig = Mage::getModel('catalog/product_media_config');
        $baseImageUrl = $productMediaConfig->getMediaUrl($product->getImage());

        return $baseImageUrl;
    }

    /**
     * @param Mixed $_product
     * @param XMLWriter $xmlWriter
     */
    function write_child_product_attributes($_product, $xmlWriter)
    {
        $xmlWriter->writeElement('g:id', $_product->getId());
        $xmlWriter->writeElement('g:image_link', $this->get_image_url($_product));

//        $this->_logger->debug('gtin: ' . $_product->getEan());
        $xmlWriter->writeElement('g:mpn', $_product->getSku());
        $xmlWriter->writeElement('display_ads_id', $_product->getSku());
        if (strlen($_product->getEan()) > 7) {
            $xmlWriter->writeElement('g:gtin', $_product->getEan());
        }

        $xmlWriter->writeElement('g:price', $_product->getPrice());
        $xmlWriter->writeElement('g:sale_price', $_product->getSpecialPrice());
        $xmlWriter->writeElement('g:size', $_product->getAttributeText('size'));
        $xmlWriter->writeElement('g:color', $_product->getAttributeText('color'));
        $xmlWriter->writeElement('g:availability', $this->do_isinstock($_product));
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
     * @param Mixed $catCollection
     * @param XMLWriter $xmlWriter
     */
    function write_product_types($catCollection, $xmlWriter)
    {
        /** @var Mixed $category */
        foreach ($catCollection as $category) {
            $xmlWriter->writeElement('g:product_type', $category->getName());
        }
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
     * @param Mixed $_product
     * @param XMLWriter $xmlWriter
     */
    private function write_configurable_product($_product, $xmlWriter)
    {
        $catCollection = $this->get_product_types($_product);

        $conf = Mage::getModel('catalog/product_type_configurable')->setProduct($_product);
        $simple_collection = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();

        foreach ($simple_collection as $_childproduct) {
            $xmlWriter->startElement('item');

            $xmlWriter->writeElement('g:item_group_id', $_product->getSku());

            // process common attributes
            $this->write_parent_product_attributes($_product, $xmlWriter);
            // process advanced attributes
            $this->write_child_product_attributes($_childproduct, $xmlWriter);
            // categories
            $this->write_product_types($catCollection, $xmlWriter);

            $xmlWriter->endElement();
            $this->count++;
        }
    }
}
