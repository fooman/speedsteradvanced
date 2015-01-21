<?php

class Fooman_SpeedsterAdvanced_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function normaliseUrl($url)
    {
        $url = trim($url);

        if (empty($url)) {
            return false;
        }

        //entries should have been entered without domain, but we try to remove the base url
        $url = str_replace(Mage::getBaseUrl(), '', $url);

        //make sure we don't start with a /
        return ltrim($url, '/');
    }
}
