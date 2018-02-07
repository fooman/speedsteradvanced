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

    /**
     * Checks if the given headers only have the content type text/html.
     * Magento sometimes sends more than one header (for instance, when loading the dashboard image). Hence, we have to
     * make sure that text/html is the ONLY header.
     *
     * @param array $headers
     *
     * @return bool whether the given headers contain a Content-Type text/html header
     */
    public function hasOnlyContentTypeHtmlHeader($headers)
    {
        $result = false;
        foreach ($headers as $header) {
            if (isset($header['name'], $header['value']) && $header['name'] === 'Content-Type') {
                if (strpos($header['value'], 'text/html') !== false) {
                    $result = true;
                } else {
                    return false;
                }
            }
        }

        return $result;
    }
}
