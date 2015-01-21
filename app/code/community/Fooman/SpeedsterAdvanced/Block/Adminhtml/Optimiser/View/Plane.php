<?php

class Fooman_SpeedsterAdvanced_Block_Adminhtml_Optimiser_View_Plane extends Mage_Adminhtml_Block_Abstract
{

    protected $_storeId = 1;
    protected $_apply = false;

    public function setStoreToCheck($storeId)
    {
        if (!empty($storeId)) {
            $this->_storeId = $storeId;
        }
        return $this;
    }

    public function setApply($apply)
    {
        $this->_apply = $apply;
        return $this;
    }

    protected function _toHtml()
    {
        $check = Mage::getModel('speedsterAdvanced/check');
        $result = $check->perform($this->_storeId, $this->_apply);
        //$result = json_decode('{"settings":{"store_id":"2","package_name":"default","theme":"modern"},"movetojs":{"default":{"js\/ie6.js":true}},"doubleup":{"print":{"prototype\/prototype.js":true,"mage\/translate.js":true,"lib\/ccard.js":true,"prototype\/validation.js":true,"varien\/js.js":true,"lib\/ds-sleight.js":true,"js\/ie6.js":true}},"movetoskin":{"catalogsearch_advanced_index":{"calendar\/calendar.js":true,"calendar\/calendar-setup.js":true},"customer_logged_in":{"varien\/weee.js":true},"customer_account_login":{"mage\/captcha.js":true},"customer_account_create":{"mage\/captcha.js":true},"customer_account_forgotpassword":{"mage\/captcha.js":true},"catalog_product_compare_index":{"scriptaculous\/scriptaculous.js":true,"varien\/product.js":true},"catalog_product_view":{"varien\/product.js":true,"varien\/configurable.js":true,"calendar\/calendar.js":true,"calendar\/calendar-setup.js":true},"catalog_product_send":{"varien\/product.js":true},"checkout_cart_index":{"varien\/weee.js":true},"review_product_list":{"varien\/product.js":true,"varien\/configurable.js":true},"checkout_onepage_index":{"mage\/directpost.js":true,"mage\/captcha.js":true,"mage\/centinel.js":true,"varien\/weee.js":true},"checkout_cart_configure":{"varien\/product.js":true,"varien\/configurable.js":true,"calendar\/calendar.js":true,"calendar\/calendar-setup.js":true},"checkout_multishipping":{"varien\/weee.js":true},"checkout_multishipping_login":{"mage\/captcha.js":true},"checkout_multishipping_register":{"mage\/captcha.js":true},"checkout_multishipping_address_select":{"varien\/weee.js":true},"checkout_multishipping_address_selectbilling":{"varien\/weee.js":true},"checkout_multishipping_address_newshipping":{"varien\/weee.js":true},"checkout_multishipping_address_newbilling":{"varien\/weee.js":true},"checkout_multishipping_address_editshipping":{"varien\/weee.js":true},"checkout_multishipping_address_editaddress":{"varien\/weee.js":true},"checkout_multishipping_address_editbilling":{"varien\/weee.js":true},"checkout_multishipping_addresses":{"varien\/weee.js":true},"checkout_multishipping_shipping":{"varien\/weee.js":true},"checkout_multishipping_billing":{"varien\/weee.js":true},"checkout_multishipping_success":{"varien\/weee.js":true},"wishlist_index_configure":{"varien\/product.js":true,"varien\/configurable.js":true,"calendar\/calendar.js":true,"calendar\/calendar-setup.js":true},"sendfriend_product_send":{"varien\/product.js":true}},"skipped":{"catalogsearch_result_index":true,"sales_order_history":true,"sales_billing_agreement_view":true,"sales_recurring_profile_view__tabs":true,"sales_recurring_profile_view":true,"sales_recurring_profile_orders":true,"checkout_multishipping_overview":true,"checkout_onepage_review":true,"paypaluk_express_review_details":true,"xmlconnect_customer_giftcardcheck":true},"noremoves":{"oauth_root_handle":{"lib\/ccard.js":true,"scriptaculous\/controls.js":true,"scriptaculous\/builder.js":true,"scriptaculous\/dragdrop.js":true,"scriptaculous\/slider.js":true,"varien\/js.js":true,"varien\/menu.js":true},"oauth_authorize_index":{"lib\/ccard.js":true,"scriptaculous\/controls.js":true,"scriptaculous\/builder.js":true,"scriptaculous\/dragdrop.js":true,"scriptaculous\/slider.js":true,"varien\/js.js":true,"varien\/menu.js":true},"oauth_authorize_confirm":{"lib\/ccard.js":true,"scriptaculous\/controls.js":true,"scriptaculous\/builder.js":true,"scriptaculous\/dragdrop.js":true,"scriptaculous\/slider.js":true,"varien\/js.js":true,"varien\/menu.js":true},"oauth_authorize_reject":{"lib\/ccard.js":true,"scriptaculous\/controls.js":true,"scriptaculous\/builder.js":true,"scriptaculous\/dragdrop.js":true,"scriptaculous\/slider.js":true,"varien\/js.js":true,"varien\/menu.js":true}}}' ,true);
        $html = '';
        $html .= '<h3>Recommendations for ' . $result['settings']['package_name'] . ' / ' . $result['settings']['theme']
            . ' (' . $result['settings']['store_id'] . ')</h3>';
        $html .= '<p>Theme Optimiser inspected your theme and makes recommendations to take advantage of SpeedsterAdvanced\'s feature to split Javascript into two files: One global file which holds the Javascript needed for every page (think libraries) and one page specific file which is variable for each page.</p>';

        $tableWidth = '33%';

        if (isset($result['fileoperations'])) {
            $html .= '<h4>File Operations</h4>';
            $html .= '<ul class="messages">';
            foreach ($result['fileoperations'] as $message) {
                $html
                    .= '<li class="' . (key($message) == 'success' ? 'success' : 'error') . '-msg">' . current($message)
                    . '</li>';
            }
            $html .= '</ul>';
        }

        if (isset($result['movetojs'])) {
            $html .= '<h4>Move to Global</h4>';
            $html .= '<table cellpadding="2" cellspacing="0" border="0" width="' . $tableWidth . '">';
            $html .= '<tr><td colspan="2">The following files are loaded in the default handle. Since they will be present on every page it makes sense to move them into the global js.</td></tr>';
            foreach ($result['movetojs'] as $handle => $files) {
                $isFirst = true;
                $html .= '<tr><td rowspan="' . count($files) . '">' . $handle . '</td>';
                foreach ($files as $file => $bool) {
                    if ($isFirst) {
                        $isFirst = false;
                    } else {
                        $html .= '<tr>';
                    }
                    $html .= '<td>' . $file . '</td></tr>';
                }
            }
            $html .= '</table>';
        }

        if (isset($result['movetoskin'])) {
            $html .= '<h4>Move to Page</h4>';
            $html .= '<table cellpadding="2" cellspacing="0" border="0" width="' . $tableWidth . '">';
            $html .= '<tr><td colspan="2">The following files are only loaded in some handles. Since they are not required on all pages it is recommended to move them into the page specific category (when running SpeedsterAdvanced) or alternatively when using Magento\'s merge JS feature into the global category.</td></tr>';
            foreach ($result['movetoskin'] as $handle => $files) {
                $isFirst = true;
                $html .= '<tr><td rowspan="' . count($files) . '">' . $handle . '</td>';
                foreach ($files as $file => $bool) {
                    if ($isFirst) {
                        $isFirst = false;
                    } else {
                        $html .= '<tr>';
                    }
                    $html .= '<td>' . $file . '</td></tr>';

                }
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        $html .= '<h4>Local.xml for move to global and move to page recommendations</h4>';
        $html .= '<p><textarea rows="20" cols="100">' . $result['local'] . '</textarea></p>';

        if (isset($result['noremoves'])) {
            $html .= '<h4>No Removes</h4>';
            $html .= '<table cellpadding="2" cellspacing="0" border="0" width="' . $tableWidth . '">';
            $html .= '<tr><td colspan="2">Instead of using the removeItem action consider keeping the file (it will most likely be already downloaded and cached anyways) or to move it out of the global scope and use page specific Javascript instead.</td></tr>';
            foreach ($result['noremoves'] as $handle => $files) {
                $isFirst = true;
                $html .= '<tr><td rowspan="' . count($files) . '">' . $handle . '</td>';
                foreach ($files as $file => $bool) {
                    if ($isFirst) {
                        $isFirst = false;
                    } else {
                        $html .= '<tr>';
                    }
                    $html .= '<td>' . $file . '</td></tr>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        /*let's not report on double ups until we can remove having to load the default handle
        if (isset($result['doubleup'])) {
            $html .= '<h4>Double Ups</h4>';
            $html .= '<table cellpadding="2" cellspacing="0" border="0" width="' . $tableWidth . '">';
            $html .= '<tr><td colspan="2">The following files are added in a non-default handle even though they are already present in the default handle.</td></tr>';
            foreach ($result['doubleup'] as $handle => $files) {
                $isFirst = true;
                $html .= '<tr><td rowspan="' . count($files) . '">' . $handle . '</td>';
                foreach ($files as $file=> $bool) {
                    if ($isFirst) {
                        $isFirst = false;
                    } else {
                        $html .= '<tr>';
                    }
                    $html .= '<td>' . $file . '</td></tr>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
        }
        */
        if (isset($result['skipped'])) {
            $html .= '<h4>Not Processed</h4>';
            $html .= '<table cellpadding="2" cellspacing="0" border="0" width="' . $tableWidth . '">';
            $html .= '<tr><td colspan="2">The following handles have not been processed by the theme optimiser. A common reason for skipped processing is that no required object was found - so for example to test sales_order_invoice an existing invoice is needed. Please review them manually.</td></tr>';
            foreach ($result['skipped'] as $handle => $files) {
                $html .= '<tr><td colspan="2">' . $handle . '</td></tr>';
            }
            $html .= '</table>';
        }

        return $html;
    }

}
