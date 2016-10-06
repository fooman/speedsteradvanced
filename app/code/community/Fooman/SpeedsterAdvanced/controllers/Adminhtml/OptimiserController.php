<?php

class Fooman_SpeedsterAdvanced_Adminhtml_OptimiserController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->setUsedModuleName('Fooman_SpeedsterAdvanced');
        $this->loadLayout()
            ->_setActiveMenu('system/tools')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('System'), Mage::helper('adminhtml')->__('System'))
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Tools'), Mage::helper('adminhtml')->__('Tools'))
            ->_addBreadcrumb(
                Mage::helper('speedsterAdvanced')->__('Speedster Optimiser'),
                Mage::helper('speedsterAdvanced')->__('Speedster Optimiser')
            );
        return $this;
    }

    public function indexAction()
    {
        if (Mage::app()->useCache('layout')) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('speedsterAdvanced')->__('Theme Optmiser can only be run when the cache is disabled.')
            );
            $this->_initAction()->renderLayout();
        } elseif (version_compare(Mage::getVersion(), '1.5.0.0', '<=')) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('speedsterAdvanced')->__('Theme Optmiser only runs on Magento 1.5+.')
            );
            $this->_redirect(Mage::getSingleton('admin/session')->getUser()->getStartupPageUrl());
        } else {
            $storeId = $this->getRequest()->getParam('store_id');
            $apply = $this->getRequest()->getParam('apply') == 'true';
            $this->_initAction()
                ->_addContent(
                    $this->getLayout()->createBlock('speedsterAdvanced/adminhtml_optimiser')->setStoreToCheck($storeId)
                        ->setApply($apply)
                )
                ->renderLayout();
        }
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('all');
    }

}
