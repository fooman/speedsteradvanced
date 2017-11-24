<?php

class Fooman_SpeedsterAdvanced_Model_Observer
{

    public function httpResponseSendBefore(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfigFlag('dev/html/minify')) {
            /** @var Mage_Core_Controller_Response_Http $response */
            $response = $observer->getData('response');
            $html     = $response->getBody();
            // only minify HTML content!
            if (!empty($html) && $html[0] !== '{'
                && Mage::helper('speedsterAdvanced')->hasContentTypeHtmlHeader($response->getHeaders())) {
                $html = Mage::getModel('speedsterAdvanced/html', $html)->process();
                $response->setBody($html);
            }
        }
    }

}
