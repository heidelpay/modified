<?php
/**
 * Invoice secured b2c payment method class
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  https://dev.heidelpay.com/modified/
 *
 * @package  heidelpay
 * @subpackage modified
 * @category modified
 */
require_once(DIR_FS_CATALOG . 'includes/classes/class.heidelpay.php');
require_once(DIR_FS_EXTERNAL . 'heidelpay/classes/heidelpayPaymentModules.php');


class hpivsec extends heidelpayPaymentModules
{
    /**
     * heidelpay insured invoice constructor
     */
    public function __construct()
    {
        $this->payCode = 'ivsec';
        parent::__construct();
    }

    /**
     * update_status
     */
    public function update_status()
    {
        global $order;

        if (($this->enabled == true) && (( int )MODULE_PAYMENT_HPIVSEC_ZONE > 0)) {
            $check_flag = false;
            $check_query = xtc_db_query("SELECT zone_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = '"
                . MODULE_PAYMENT_HPZONE . "' AND zone_country_id = '" . $order->billing['country']['id']
                . "' ORDER BY zone_id");
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
        // call parent selection
        $content = parent::selection();

        // reset session data
        if (strpos($_SERVER['SCRIPT_FILENAME'], 'checkout_payment') !== false) {
            unset($_SESSION['hpLastData']);
        }

        // set a flag if the customer was denied by insurance provider
        if (isset($_SESSION['hpResponse']['INSURANCE-RESERVATION'], $_GET['payment_error'])) {
            if ($_GET['payment_error'] === 'hpivsec' && $_SESSION['hpResponse']['INSURANCE-RESERVATION'] === 'DENIED') {
                $_SESSION['hpSecIvDenied'] = true;
                unset($_SESSION['hpResponse']['INSURANCE-RESERVATION']);
            }
        }

        // disables paymethod for the time of the actual session if the customer was rejected by insurance provider
        if (isset($_SESSION['hpSecIvDenied']) && $_SESSION['hpSecIvDenied'] == true) {
            return false;
        }

        // estimate weather this payment method is available
        if ($this->isAvailable() === false) {
            return false;
        }
        // only usable for b2c customers
        if ($this->isCompany()) {
            return false;
        }

        // billing and delivery address has to be equal
        if ($this->equalAddress() === false) {
            return array(
                'id' => $this->code,
                'module' => $this->title,
                'fields' => array(
                    array(
                        'title' => '',
                        'field' => constant('MODULE_PAYMENT_HP' . strtoupper($this->payCode) . '_ADDRESSCHECK')
                    )
                ),
                'description' => $this->info
            );
        }

        // Salutation select field
        $content[] = $this->salutationSelection();

        //Birthday select
        $content[] = $this->birthDateSelection();


        return array(
            'id' => $this->code,
            'module' => $this->title,
            'fields' => $content,
            'description' => $this->info
        );
    }

    public function pre_confirmation_check()
    {
        if ($_POST['hpivsec']['salutation'] == '' or
            $_POST['hpivsec']['day'] == '' or
            $_POST['hpivsec']['month'] == '' or
            $_POST['hpivsec']['year'] == '' or
            $this->equalAddress() === false
        ) {
            $payment_error_return = 'payment_error=HPDDSEC&error=' . urlencode(MODULE_PAYMENT_HPDDSEC_PAYMENT_DATA);
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
            return;
        }
        $_SESSION['hpModuleMode'] = 'AFTER';
        $_SESSION['hpLastPost'] = $_POST;
        $_SESSION['hpivsecData'] = $_POST['hpivsec'];
    }

    public function after_process()
    {
        global $order, $insert_id;
        $this->hp->setOrderStatus($insert_id, $this->order_status);
        $this->hp->addHistoryComment($insert_id, '', $this->order_status);
        $this->hp->handleDebit($order, $this->payCode, $insert_id);
        return true;
    }

    public function check()
    {
        if (!isset($this->_check)) {
            $check_query = xtc_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION
                . " WHERE configuration_key = 'MODULE_PAYMENT_HPIVSEC_STATUS'");
            $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }

    public function install()
    {
        $this->remove(true);

        $prefix = 'MODULE_PAYMENT_HPIVSEC_';
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
            'configuration_value' => '31HA07BC81856CAD6D8E05CDDE7E2AC8'
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
            'configuration_value' => '2.2'
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
            'configuration_key' => $prefix . 'FINISHED_STATUS_ID',
            'configuration_value' => '0',
            'set_function' => 'xtc_cfg_pull_down_order_statuses(',
            'use_function' => 'xtc_get_order_status_name'
        );
        $inst[] = array(
            'configuration_key' => $prefix . 'PROCESSED_STATUS_ID',
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
        $prefix = 'MODULE_PAYMENT_HPIVSEC_';
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
            $prefix . 'FINISHED_STATUS_ID',
            $prefix . 'PROCESSED_STATUS_ID',
            $prefix . 'CANCELED_STATUS_ID',
            $prefix . 'NEWORDER_STATUS_ID',
            $prefix . 'SORT_ORDER',
            $prefix . 'ALLOWED',
            $prefix . 'ZONE'
        );
    }
}
