<?php

class Fooman_SpeedsterAdvanced_Model_System_ExcludeList extends Mage_Core_Model_Config_Data
{

    protected function _beforeSave()
    {
        $this->setValue(str_replace(array("\r", " "), "", str_replace("\n", ",", $this->getValue())));
        return parent::_beforeSave();
    }

    protected function _afterLoad()
    {
        $this->setValue(str_replace(",", "\n", $this->getValue()));
        return parent::_afterLoad();
    }
}