<?php

class Fooman_SpeedsterAdvanced_Model_Check
{

    protected $_allHandles = array();
    protected $_suggestions = array();
    protected $_defaultItems = array();
    protected $_counter = array();
    protected $_recommendations = array();

    protected $_storeId;
    protected $_apply;
    protected $_skinDefaultDir;
    protected $_localXmlFilename;
    protected $_local;
    protected $_existingUmask;

    public function perform($storeId = 1, $apply = false)
    {

        $this->_storeId = $storeId;
        $this->_apply = $apply;
        $this->_existingUmask = 0777;//umask();

        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initial = $appEmulation->startEnvironmentEmulation(
            $this->_storeId, Mage_Core_Model_App_Area::AREA_FRONTEND, true
        );

        $this->_skinDefaultDir = Mage::getDesign()->getSkinBaseDir();

        $this->_recommendations['settings']['store_id'] = $storeId;
        $this->_recommendations['settings']['package_name'] = Mage::getDesign()->getPackageName();
        $this->_recommendations['settings']['theme'] = Mage::getDesign()->getTheme('layout');
        $this->_localXmlFilename
            = Mage::getBaseDir('design') . DS . 'frontend' . DS . Mage::getDesign()->getPackageName() . DS
            . Mage::getDesign()->getTheme('layout') . DS . 'layout' . DS . 'local.xml';

        $layout = Mage::app()->getLayout();
        $update = $layout->getUpdate();
        //reset needed since otherwise only packages related to adminhtml are loaded
        $update->resetPackageLayout();
        $xml = $update->getFileLayoutUpdatesXml('frontend', 'base', 'default');

        Mage::app()->removeCache($update->getCacheId());
        $update->resetHandles();
        $update->resetUpdates();


        $this->loadDefault();

        foreach ($xml->children() as $handle => $node) {
            Mage::log($handle);
            try {
                $this->processHandle($handle);
            } catch (Exception $e) {
                $this->_recommendations['skipped'][$handle] = true;
            }
        }
        $appEmulation->stopEnvironmentEmulation($initial);

        $this->convertRecommendationsToLocalXml();

        //umask($this->_existingUmask);
        return $this->_recommendations;

    }

    public function loadDefault()
    {
        $registryItems = array(
            'current_category' => 'catalog/category',
            'product'          => 'catalog/product',
            'current_tag'      => 'tag/tag',
            'current_customer' => 'customer/customer',
            'current_order'    => 'sales/order',
            'sales/recurring_profile',
            'sales/billing_agreement',
            'wishlist_item'    => 'catalog/product'
        );
        foreach ($registryItems as $registryKey => $model) {
            $collection = Mage::getModel($model)->getCollection();
            if ($collection) {
                $collection->setPageSize(1)->setCurPage(1);
                if (count($collection)) {
                    Mage::register($registryKey, $collection->getFirstItem());
                }
            }
        }
        if (Mage::registry('current_tag')) {
            Mage::register('tag_model', Mage::registry('current_tag'));
        }
        $this->processHandle('default');
    }

    public function processHandle($handle)
    {
        $layout = Mage::app()->getLayout();
        $update = $layout->getUpdate();
        Mage::app()->removeCache($update->getCacheId());

        $update->resetHandles();
        $update->resetUpdates();

        if (!$this->specialHandlingForHandle($handle, $update)) {

            $update->load($handle);
            $layout->generateXml();
            $layout->generateBlocks();

            $actions = $layout->getXpath("//*[@name='head']/action");
            if ($actions) {
                foreach ($actions as $action) {
                    if ($handle == 'default') {
                        //prefill default content for evaluation below
                        switch ((string)$action->attributes()->method) {
                            case 'addJs':
                                //case 'addJsIe':
                                $fileName = (string)$action->script ? (string)$action->script : (string)$action->file;
                                $this->_defaultItems['js'][$fileName] = true;
                                break;
                            case 'addItem':
                                switch ((string)$action->type) {
                                    case 'js':
                                        $this->_defaultItems['js'][(string)$action->name] = true;
                                        break;
                                    case 'skin_js':
                                        if (!isset($action->if)) {
                                            $this->_recommendations['movetojs'][$handle][(string)$action->name] = true;
                                        }
                                        $this->_defaultItems['js'][(string)$action->name] = true;
                                        break;
                                }
                                break;
                        }
                    } else {
                        switch ((string)$action->attributes()->method) {
                            case 'addJs':
                                //case 'addJsIe':
                                $fileName = (string)$action->script ? (string)$action->script : (string)$action->file;
                                if (isset($this->_defaultItems['js'][$fileName])) {
                                    $this->_recommendations['doubleup'][$handle][$fileName] = true;
                                } else {
                                    $this->_recommendations['movetoskin'][$handle][$fileName] = true;
                                    if ($this->_apply) {
                                        $newFileName = $this->_skinDefaultDir . DS . 'js' . DS . $fileName;
                                        $existing = Mage::getBaseDir() . DS . 'js' . DS . $fileName;
                                        try {
                                            if (!file_exists($newFileName)) {
                                                if (!is_dir(dirname($newFileName))) {
                                                    mkdir(dirname($newFileName), $this->_existingUmask, true);
                                                }
                                                $result = copy($existing, $newFileName);
                                                if ($result) {
                                                    $this->_recommendations['fileoperations'][] = array('success' =>
                                                                                                            'File copied to '
                                                                                                            . $newFileName);
                                                } else {
                                                    $this->_recommendations['fileoperations'][] = array('error' =>
                                                                                                            'Could not copy '
                                                                                                            . $existing
                                                                                                            . ' to '
                                                                                                            . $newFileName);
                                                }
                                            }
                                        } catch (Exception $e) {
                                            $this->_recommendations['fileoperations'][] = array('error' =>
                                                                                                    'Could not copy '
                                                                                                    . $existing . ' to '
                                                                                                    . $newFileName);
                                        }
                                    }
                                }
                                break;
                            case 'addItem':
                                switch ((string)$action->type) {
                                    case 'js':
                                        if (isset($this->_defaultItems['js'][(string)$action->name])) {
                                            $this->_recommendations['doubleup'][$handle][(string)$action->name] = true;
                                        } else {
                                            $this->_recommendations['movetoskin'][$handle][(string)$action->name]
                                                = true;
                                            if ($this->_apply) {
                                                $newFileName
                                                    = $this->_skinDefaultDir . DS . 'js' . DS . (string)$action->name;
                                                $existing = Mage::getBaseDir() . DS . 'js' . DS . (string)$action->name;
                                                try {
                                                    if (!file_exists($newFileName)) {
                                                        if (!is_dir(dirname($newFileName))) {
                                                            mkdir(dirname($newFileName), $this->_existingUmask, true);
                                                        }
                                                        $result = copy($existing, $newFileName);
                                                        if ($result) {
                                                            $this->_recommendations['fileoperations'][]
                                                                = array('success' => 'File copied to ' . $newFileName);
                                                        } else {
                                                            $this->_recommendations['fileoperations'][]
                                                                = array('error' =>
                                                                            'Could not copy ' . $existing . ' to '
                                                                            . $newFileName);
                                                        }
                                                    }
                                                } catch (Exception $e) {
                                                    $this->_recommendations['fileoperations'][] = array('error' =>
                                                                                                            'Could not copy '
                                                                                                            . $existing
                                                                                                            . ' to '
                                                                                                            . $newFileName
                                                                                                            . ' '
                                                                                                            . $e->getMessage(
                                                                                                            ));
                                                }
                                            }
                                        }
                                        break;
                                    case 'skin_js':
                                        if (isset($this->_defaultItems['js'][(string)$action->name])) {
                                            $this->_recommendations['doubleup'][$handle][(string)$action->name] = true;
                                        }
                                        break;
                                }
                                break;
                            case 'removeItem':
                                //Instead of removing it is preferable to remove from default and add on handles where required
                                switch ((string)$action->type) {
                                    case 'js':
                                        if (isset($this->_defaultItems['js'][(string)$action->name])) {
                                            //disable until reconciled with other recommendations
                                            //$this->_recommendations['noremoves'][$handle][(string)$action->name] = true;
                                        }
                                        //we have implemented the suggestion
                                        if (isset($this->_recommendations['movetoskin'][$handle][(string)$action->name])) {
                                            unset($this->_recommendations['movetoskin'][$handle][(string)$action->name]);
                                            if (empty($this->_recommendations['movetoskin'][$handle])) {
                                                unset($this->_recommendations['movetoskin'][$handle]);
                                                if (empty($this->_recommendations['movetoskin'])) {
                                                    unset($this->_recommendations['movetoskin']);
                                                }
                                            }
                                        }
                                        break;
                                }
                                break;
                        }
                    }
                }
            }
        } else {
            $this->_recommendations['skipped'][$handle] = true;
        }
    }

    public function specialHandlingForHandle($handle, $update)
    {
        $toSkip = array(
            'catalogsearch_result_index', 'checkout_multishipping_overview', 'checkout_onepage_review',
            'paypaluk_express_review_details', 'print', 'enterprise_rma_return_view'
        );
        if (in_array($handle, $toSkip)) {
            return true;
        }
        if ($handle == 'xmlconnect_customer_giftcardcheck') {
            //requires enterprise
            return true;
        }

        if ($handle == 'sales_order_history') {
            //retrieves layout hardcoded from Mage::app()->getFrontController()->getAction()->getLayout() ...
            return true;
        }

        if ($handle == 'rss_catalog_tag' || $handle == 'tag_list_index' || $handle == 'tag_product_list'
            || $handle == 'tag_product_list'
            || $handle == 'tag_customer_index'
            || $handle == 'tag_customer_view'
        ) {
            return !(bool)Mage::registry('tag_model');
        }

        $salesHandles = array(
            'sales_order_view',
            'sales_order_invoice',
            'sales_order_shipment',
            'sales_order_creditmemo',
            'sales_order_reorder',
            'sales_order_print',
            'sales_order_printinvoice',
            'sales_order_printshipment',
            'sales_order_printcreditmemo',
            'sales_guest_view',
            'sales_guest_invoice',
            'sales_guest_shipment',
            'sales_guest_creditmemo',
            'sales_guest_reorder',
            'sales_guest_print',
            'sales_guest_printinvoice',
            'sales_guest_printshipment',
            'sales_guest_printcreditmemo',
        );
        if (in_array($handle, $salesHandles)) {
            return !(bool)Mage::registry('current_order');
        }

        if ($handle == 'sales_billing_agreement_view') {
            return !(bool)Mage::registry('current_billing_agreement');
        }

        if ($handle == 'sales_recurring_profile_index' || $handle == 'sales_recurring_profile_view__tabs'
            || $handle == 'sales_recurring_profile_view'
            || $handle == 'sales_recurring_profile_orders'
        ) {
            return !(bool)Mage::registry('current_recurring_profile');
        }

        if ($handle == 'wishlist_index_configure') {
            return !(bool)Mage::registry('product');
        }

        if ($handle == 'cms_page') {
            //requires root element
            $update->load('default');
        }

        return false;
    }

    public function convertRecommendationsToLocalXml()
    {
        $layoutElementClass = Mage::getConfig()->getModelClassName('core/layout_element');
        $this->_local = simplexml_load_string('<layout/>', $layoutElementClass);
        $this->_local->addAttribute('version', '0.1.0');

        if (isset($this->_recommendations['movetojs'])) {
            foreach ($this->_recommendations['movetojs'] as $handle => $files) {
                $handleXml = $this->_local->addChild($handle);
                $referenceXml = $handleXml->addChild('reference');
                $referenceXml->addAttribute('name', 'head');
                foreach ($files as $file => $bool) {
                    $actionXml = $referenceXml->addChild('action');
                    $actionXml->addAttribute('method', 'removeItem');
                    $actionXml->addChild('type', 'skin_js');
                    $actionXml->addChild('name', $file);

                    $actionXml = $referenceXml->addChild('action');
                    $actionXml->addAttribute('method', 'addItem');
                    $actionXml->addChild('type', 'js');
                    $actionXml->addChild('name', $file);
                }
            }
        }
        if (isset($this->_recommendations['movetoskin'])) {
            foreach ($this->_recommendations['movetoskin'] as $handle => $files) {

                $handleXml = $this->_local->addChild($handle);
                $referenceXml = $handleXml->addChild('reference');
                $referenceXml->addAttribute('name', 'head');

                foreach ($files as $file => $bool) {
                    $actionXml = $referenceXml->addChild('action');
                    $actionXml->addAttribute('method', 'removeItem');
                    $actionXml->addChild('type', 'js');
                    $actionXml->addChild('name', $file);

                    $actionXml = $referenceXml->addChild('action');
                    $actionXml->addAttribute('method', 'addItem');
                    $actionXml->addChild('type', 'skin_js');
                    $actionXml->addChild('name', 'js/' . $file);
                }
            }
        }
        $this->_recommendations['local'] = $this->_local->asNiceXml();
        if ($this->_apply) {
            if (!file_exists($this->_localXmlFilename)) {
                try {
                    if (!is_dir(dirname($this->_localXmlFilename))) {
                        mkdir(dirname($this->_localXmlFilename), $this->_existingUmask, true);
                    }
                    file_put_contents($this->_localXmlFilename, $this->_recommendations['local']);
                    $this->_recommendations['fileoperations'][] = array('success' => 'Created local.xml file for Theme '
                        . $this->_localXmlFilename);
                } catch (Exception $e) {
                    $this->_recommendations['fileoperations'][] = array('error' =>
                                                                            'Could not create local.xml file for Theme '
                                                                            . $this->_localXmlFilename . ' '
                                                                            . $e->getMessage());
                }

            }
        }
    }

}
