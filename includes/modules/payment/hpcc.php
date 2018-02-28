<?php
/**
 * credit card payment method class
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link  https://dev.heidelpay.de/modified/
 *
 * @package  heidelpay
 * @subpackage modified
 * @category modified
 */
require_once(DIR_FS_CATALOG . 'includes/classes/class.heidelpay.php');
require_once(DIR_FS_EXTERNAL . 'heidelpay/classes/heidelpayPaymentModules.php');


class hpcc extends heidelpayPaymentModules
{
    // class constructor
    public function __construct()
    {
        $this->payCode = 'cc';
        parent::__construct();
    }

    public function update_status()
    {
        global $order;

        if (($this->enabled == true) && (( int )MODULE_PAYMENT_HPCC_ZONE > 0)) {
            $check_flag = false;
            $check_query = xtc_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '"
                . MODULE_PAYMENT_HPCC_ZONE . "' and zone_country_id = '"
                . $order->billing['country']['id'] . "' order by zone_id");
            while ($check = xtc_db_fetch_array($check_query)) {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $order->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }

            if ($check_flag == false) {
                $this->enabled = false;
            }
        }
    }

    public function selection()
    {
        global $order;
        if (strpos($_SERVER['SCRIPT_FILENAME'], 'checkout_payment') !== false) {
            // coupon reset
            if (!empty($_SESSION['cc_id'])) {
                $_SESSION['heidel_last_coupon'] = $_SESSION['cc_id'];
            } elseif (!empty($_SESSION['heidel_last_coupon'])) {
                $_SESSION['cc_id'] = $_SESSION['heidel_last_coupon'];
            }
            unset($_SESSION['hpLastData']);
            unset($_SESSION['hpUseUniqueId']);
        }
        if ($_SESSION['customers_status']['customers_status_show_price_tax'] == 0 &&
            $_SESSION['customers_status']['customers_status_add_tax_ot'] == 1
        ) {
            $total = $order->info['total'] + $order->info['tax'];
        } else {
            $total = $order->info['total'];
        }
        $total = $total * 100;
        if (MODULE_PAYMENT_HPCC_MIN_AMOUNT > 0 && MODULE_PAYMENT_HPCC_MIN_AMOUNT > $total) {
            return false;
        }
        if (MODULE_PAYMENT_HPCC_MAX_AMOUNT > 0 && MODULE_PAYMENT_HPCC_MAX_AMOUNT < $total) {
            return false;
        }

        $src = $this->hp->handleRegister($order, $this->payCode);

        $hpIframe = '';
        if (!empty($src)) {
            $hpIframe = '<iframe src="' . $src . '" frameborder="0" width="400" height="250"></iframe>';
            $hpIframe .= '<script>'
                . 'function switchFrameCC(val)'
                . '{'
                .'var objLast = document.getElementById("lastCCard");'
                . 'var objNew = document.getElementById("newCCard");'
                . 'if(val.checked){'
                . 'objLast.style.display = "block";'
                . 'objNew.style.display = "none";'
                . '}else{'
                . 'objLast.style.display = "none";'
                . 'objNew.style.display = "block";'
                . '}'
                . '}'
                . '</script>';
        }

        if (MODULE_PAYMENT_HPCC_TRANSACTION_MODE == 'LIVE' || strpos(MODULE_PAYMENT_HPCC_TEST_ACCOUNT,
                $order->customer['email_address']) !== false
        ) {
            $content = array();
            if (MODULE_PAYMENT_HPCC_MODULE_MODE == 'DIRECT') {
                // Special CC Reuse
                $lastCCard = $this->hp->loadMEMO($_SESSION['customer_id'], 'heidelpay_last_ccard');
                // if(!empty($lastCCard)){
                $gender = $_SESSION['customer_gender'] == 'f' ? FEMALE : MALE;
                $name = $_SESSION['customer_last_name'];
                if (!empty($lastCCard)) {
                    $title = $gender . ' ' . $name . ', ' . MODULE_PAYMENT_HPCC_REUSE_CARD
                        . '<br>' . '<div id="lastCCard">' . MODULE_PAYMENT_HPCC_REUSE_CARD_NUMBER
                        . $lastCCard . '</div>' . '<input type="hidden" name="hpccUniqueId" id="hpccUniqueId" value="">'
                        . '<input type="checkbox" name="hpccReuseCard" value="1"'
                        . ' checked onChange="switchFrameCC(this)">'
                        . MODULE_PAYMENT_HPCC_REUSE_CARD_TEXT . '<div id="newCCard" style="display: none">'
                        . $hpIframe . '</div>';
                } else {
                    $title = '<div id="newCCard" style="display: block">' . $hpIframe . '</div>'
                        . '<input type="hidden" name="hpccUniqueId" id="hpccUniqueId" value="">';
                }
                $content[] = array(
                    'title' => $title,
                    'field' => ''
                );
                // }
            }
        } else {
            $content = array(
                array(
                    'title' => '',
                    'field' => MODULE_PAYMENT_HPCC_DEBUGTEXT
                )
            );
        }

        return array(
            'id' => $this->code,
            'module' => $this->title,
            'fields' => $content,
            'description' => $this->info
        );
    }

    public function pre_confirmation_check()
    {
        global $order;
        if (MODULE_PAYMENT_HPCC_TRANSACTION_MODE == 'LIVE' ||
            strpos(MODULE_PAYMENT_HPCC_TEST_ACCOUNT, $order->customer['email_address']) !== false
        ) {
            $_SESSION['hpModuleMode'] = MODULE_PAYMENT_HPCC_MODULE_MODE;
            if ($_POST['hpccReuseCard'] == 1) {
                $uniqueID = $this->hp->loadMEMO($_SESSION['customer_id'], 'heidelpay_last_ccard_reference');
                if (!empty($uniqueID)) {
                    $_SESSION['hpUseUniqueId'] = $uniqueID;
                }
            } elseif ($_POST['hpccUniqueId'] != '') {
                $uniqueID = $_POST['hpccUniqueId'];
                if (!empty($uniqueID)) {
                    $_SESSION['hpUseUniqueId'] = $uniqueID;
                }
            } elseif (MODULE_PAYMENT_HPCC_MODULE_MODE == 'DIRECT') {
                $payment_error_return = 'payment_error=hpcc&error=' . MODULE_PAYMENT_HPCC_ERROR_NO_PAYDATA;
                xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
            }
            if (!isset($_SESSION['hpUseUniqueId']) && $_POST['hpccFirstStep'] == 1 &&
                MODULE_PAYMENT_HPCC_MODULE_MODE == 'DIRECT'
            ) {
                unset($_POST['hpccFirstStep']);
                $_SESSION['hpLastPost'] = $_POST;
                $payment_error_return = 'hpccreg=1';
                xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
            } else {
                $_SESSION['hpLastPost'] = $_POST;
            }
        } else {
            $payment_error_return = 'payment_error=hpcc&error=' . urlencode(MODULE_PAYMENT_HPCC_DEBUGTEXT);
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
        }
    }

    public function confirmation()
    {
        if (!isset($_SESSION['hpUseUniqueId'])) {
            return false;
        }
        // Special CC Reuse
        $lastCCard = $this->hp->loadMEMO($_SESSION['customer_id'], 'heidelpay_last_ccard');
        $content = array();
        if (!empty($lastCCard)) {
            $gender = $_SESSION['customer_gender'] == 'f' ? FEMALE : MALE;
            $name = $_SESSION['customer_last_name'];
            $content[] = array(
                'title' => MODULE_PAYMENT_HPCC_WILLUSE_CARD . $lastCCard,
                'field' => ''
            );
        } else {
            return false;
        }
        return array(
            'title' => $gender . ' ' . $name . ', ',
            'fields' => $content
        );
    }

    public function after_process()
    {
        global $order, $insert_id;
        $this->hp->setOrderStatus($insert_id, $this->order_status);
        $comment = ' ';
        $this->hp->addHistoryComment($insert_id, $comment, $this->order_status);
        $this->hp->handleDebit($order, $this->payCode, $insert_id);
        return true;
    }

    public function check()
    {
        if (!isset($this->_check)) {
            $check_query = xtc_db_query("select configuration_value from "
                . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_HPCC_STATUS'");
            $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }

    public function install()
    {
        $this->remove(true);

        $prefix = 'MODULE_PAYMENT_HPCC_';
        $inst = array();
        $inst[] = array(
            'configuration_key' => $prefix . 'STATUS',
            'configuration_value' => 'True',
            'set_function' => 'xtc_cfg_select_option(array(\'True\', \'False\'), '
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'SECURITY_SENDER',
            'configuration_value' => '31HA07BC8142C5A171745D00AD63D182'
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'USER_LOGIN',
            'configuration_value' => '31ha07bc8142c5a171744e5aef11ffd3'
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'USER_PWD',
            'configuration_value' => '93167DE7'
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'TRANSACTION_CHANNEL',
            'configuration_value' => '31HA07BC8142C5A171744F3D6D155865'
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'TRANSACTION_MODE',
            'configuration_value' => 'TEST',
            'set_function' => 'xtc_cfg_select_option(array(\'LIVE\', \'TEST\'), '
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'MODULE_MODE',
            'configuration_value' => 'DIRECT',
            'set_function' => 'xtc_cfg_select_option(array(\'DIRECT\', \'AFTER\'), '
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'PAY_MODE',
            'configuration_value' => 'DB',
            'set_function' => 'xtc_cfg_select_option(array(\'DB\', \'PA\'), '
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'SAVE_REGISTER',
            'configuration_value' => 'True',
            'set_function' => 'xtc_cfg_select_option(array(\'True\', \'False\'), '
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'TEST_ACCOUNT',
            'configuration_value' => ''
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'SORT_ORDER',
            'configuration_value' => '1.1'
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'ZONE',
            'configuration_value' => '',
            'set_function' => 'xtc_cfg_pull_down_zone_classes(',
            'use_function' => 'xtc_get_zone_class_title'
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'ALLOWED',
            'configuration_value' => ''
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'MIN_AMOUNT',
            'configuration_value' => ''
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'MAX_AMOUNT',
            'configuration_value' => ''
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'PROCESSED_STATUS_ID',
            'configuration_value' => '0',
            'set_function' => 'xtc_cfg_pull_down_order_statuses(',
            'use_function' => 'xtc_get_order_status_name'
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'PENDING_STATUS_ID',
            'configuration_value' => '0',
            'set_function' => 'xtc_cfg_pull_down_order_statuses(',
            'use_function' => 'xtc_get_order_status_name'
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'CANCELED_STATUS_ID',
            'configuration_value' => '0',
            'set_function' => 'xtc_cfg_pull_down_order_statuses(',
            'use_function' => 'xtc_get_order_status_name'
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'NEWORDER_STATUS_ID',
            'configuration_value' => '0',
            'set_function' => 'xtc_cfg_pull_down_order_statuses(',
            'use_function' => 'xtc_get_order_status_name'
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'DEBUG',
            'configuration_value' => 'False',
            'set_function' => 'xtc_cfg_select_option(array(\'True\', \'False\'), '
        );

        $this->defaultConfigSettings($inst);
    }

    public function keys()
    {
        $prefix = 'MODULE_PAYMENT_HPCC_';
        return array(
            $prefix . 'STATUS',
            $prefix . 'SECURITY_SENDER',
            $prefix . 'USER_LOGIN',
            $prefix . 'USER_PWD',
            $prefix . 'TRANSACTION_CHANNEL',
            $prefix . 'TRANSACTION_MODE',
            $prefix . 'MODULE_MODE',
            $prefix . 'SAVE_REGISTER',
            $prefix . 'PAY_MODE',
            $prefix . 'TEST_ACCOUNT',
            $prefix . 'MIN_AMOUNT',
            $prefix . 'MAX_AMOUNT',
            $prefix . 'PROCESSED_STATUS_ID',
            $prefix . 'PENDING_STATUS_ID',
            $prefix . 'CANCELED_STATUS_ID',
            $prefix . 'NEWORDER_STATUS_ID',
            $prefix . 'SORT_ORDER',
            $prefix . 'ALLOWED',
            $prefix . 'ZONE',
            $prefix . 'DEBUG'
        );
    }
}
