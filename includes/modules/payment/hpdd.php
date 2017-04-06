<?php
/**
 * Sepa direct debit payment method class
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

class hpdd extends heidelpayPaymentModules
{
    /**
     * heidelpay sepa direct debit constructor
     */
    public function __construct()
    {
        $this->payCode = 'dd';
        $this->code = 'hp' . $this->payCode;
        $this->title = MODULE_PAYMENT_HPDD_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_HPDD_TEXT_DESC;
        $this->sort_order = MODULE_PAYMENT_HPDD_SORT_ORDER;
        $this->enabled = (MODULE_PAYMENT_HPDD_STATUS == 'True') ? true : false;
        $this->info = MODULE_PAYMENT_HPDD_TEXT_INFO;
        $this->tmpStatus = MODULE_PAYMENT_HPDD_NEWORDER_STATUS_ID;
        $this->order_status = MODULE_PAYMENT_HPDD_NEWORDER_STATUS_ID;

        parent::__construct();
    }

    /**
     * update_status
     */
    public function update_status()
    {
        global $order;

        if (($this->enabled == true) && (( int )MODULE_PAYMENT_HPDD_ZONE > 0)) {
            $check_flag = false;
            $check_query = xtc_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES
                . " where geo_zone_id = '" . MODULE_PAYMENT_HPDD_ZONE . "' and zone_country_id = '"
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

    /**
     * checkout payment form
     *
     * @return array|bool
     */
    public function selection()
    {
        // call parent selection
        $content = parent::selection();

        // reset session data
        if (strpos($_SERVER['SCRIPT_FILENAME'], 'checkout_payment') !== false) {
            unset($_SESSION['hpLastData']);
            unset($_SESSION['hpDDData']);
        }

        // estimate weather this payment method is available
        if ($this->isAvailable() === false) {
            return false;
        }

        // Holder input field
        $content[] = $this->accountHolderSelection();

        // Iban input field
        $content[] = $this->accountIbanSelection();

        return array(
            'id' => $this->code,
            'module' => $this->title,
            'fields' => $content,
            'description' => $this->info
        );
    }

    public function pre_confirmation_check()
    {
        if ((($_POST['hpdd']['AccountIBAN'] == '')) or ($_POST['hpdd']['Holder'] == '')) {
            $payment_error_return = 'payment_error=hpdd&error=' . urlencode(MODULE_PAYMENT_HPDD_PAYMENT_DATA);
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
            return;
        }
        $_SESSION['hpLastPost'] = $_POST;
        $_SESSION['hpDDData'] = $_POST['hpdd'];
    }

    public function after_process()
    {
        global $order, $insert_id;
        $this->hp->setOrderStatus($insert_id, $this->order_status);
        $this->hp->addHistoryComment($insert_id, '', $this->order_status);
        $this->hp->handleDebit($order, $this->payCode, $insert_id);
        return true;
    }

    public function get_error()
    {
        global $_GET;

        $error = array(
            'title' => MODULE_PAYMENT_HPDD_TEXT_ERROR,
            'error' => stripslashes(urldecode($_GET['error']))
        );

        return $error;
    }

    public function check()
    {
        if (!isset($this->_check)) {
            $check_query = xtc_db_query("select configuration_value from "
                . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_HPDD_STATUS'");
            $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }

    public function install()
    {
        $this->remove(true);

        $prefix = 'MODULE_PAYMENT_HPDD_';
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
            'configuration_key' => $prefix . 'TEST_ACCOUNT',
            'configuration_value' => ''
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'SORT_ORDER',
            'configuration_value' => '1.3'
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

        parent::defaultConfigSettings($inst);
    }

    public function keys()
    {
        $prefix = 'MODULE_PAYMENT_HPDD_';
        return array(
            $prefix . 'STATUS',
            $prefix . 'SECURITY_SENDER',
            $prefix . 'USER_LOGIN',
            $prefix . 'USER_PWD',
            $prefix . 'TRANSACTION_CHANNEL',
            $prefix . 'TRANSACTION_MODE',
            $prefix . 'TEST_ACCOUNT',
            $prefix . 'MIN_AMOUNT',
            $prefix . 'MAX_AMOUNT',
            $prefix . 'PROCESSED_STATUS_ID',
            $prefix . 'CANCELED_STATUS_ID',
            $prefix . 'NEWORDER_STATUS_ID',
            $prefix . 'SORT_ORDER',
            $prefix . 'ALLOWED',
            $prefix . 'ZONE'
        );
    }
}
