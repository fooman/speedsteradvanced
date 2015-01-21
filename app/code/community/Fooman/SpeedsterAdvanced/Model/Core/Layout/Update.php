<?php

class Fooman_SpeedsterAdvanced_Model_Core_Layout_Update extends Mage_Core_Model_Layout_Update
{

    public function resetPackageLayout()
    {
        $this->_packageLayout = false;
    }

}
