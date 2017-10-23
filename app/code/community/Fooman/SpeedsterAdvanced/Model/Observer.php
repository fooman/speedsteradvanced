<?php

class Fooman_SpeedsterAdvanced_Model_Observer
{

    public function httpResponseSendBefore(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfigFlag('dev/html/minify')) {
            /** @var Mage_Core_Controller_Response_Http $response */
            $response = $observer->getData('response');
            // only minify HTML content!
            if (Mage::helper('speedsterAdvanced')->hasContentTypeHtmlHeader($response->getHeaders())) {
                $html = $response->getBody();
                $html = Mage::getModel('speedsterAdvanced/html', $html)->minify($html);
                $response->setBody($html);
            }
        }
    }

}
