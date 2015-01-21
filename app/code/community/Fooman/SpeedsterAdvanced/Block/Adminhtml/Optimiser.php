<?php

class Fooman_SpeedsterAdvanced_Block_Adminhtml_Optimiser extends Mage_Adminhtml_Block_Widget_Container
{

    public function setStoreToCheck($storeId)
    {
        $this->getChild('plane')->setStoreToCheck($storeId);
        return $this;
    }

    public function setApply($apply)
    {
        $this->getChild('plane')->setApply($apply);
        return $this;
    }

    public function __construct()
    {
        $this->_addButton(
            'apply', array(
                'label'   => Mage::helper('speedsterAdvanced')->__('Apply Recommendations'),
                'class'   => 'scalable save',
                'onclick' => 'window.location.href=\'' . $this->getApplyUrl() . '\'',
            )
        );

        $stores = Mage::getModel('core/store')->getCollection();
        foreach ($stores as $store) {
            $this->_addButton(
                'view' . $store->getId(), array(
                    'label'   => $store->getName(),
                    'class'   => 'view',
                    'onclick' => 'window.location.href=\'' . $this->getViewUrl($store->getId()) . '\'',
                )
            );
        }

        $this->_blockGroup = 'speedsterAdvanced';
        $this->_controller = 'adminhtml_optimiser';
        $this->_headerText = Mage::helper('speedsterAdvanced')->__('SpeedsterAdvanced - Theme Optimiser');

        parent::__construct();

        $this->setTemplate('widget/view/container.phtml');
        $this->_removeButton('back');
        $this->_removeButton('edit');
    }

    protected function _prepareLayout()
    {
        $this->setChild(
            'plane', $this->getLayout()->createBlock('speedsterAdvanced/' . $this->_controller . '_view_plane')
        );
        return parent::_prepareLayout();
    }

    public function getViewUrl($id)
    {
        return $this->getUrl('*/*/*', array('store_id' => $id));
    }

    public function getApplyUrl()
    {
        return $this->getUrl('*/*/*', array('apply' => 'true', '_current' => true));
    }

    public function getViewHtml()
    {
        return $this->getChildHtml('plane');
    }
}
