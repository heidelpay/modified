<?php
if (file_exists(DIR_WS_CLASSES . 'class.heidelpay.php')) {
    include_once(DIR_WS_CLASSES . 'class.heidelpay.php');
} else {
    require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'class.heidelpay.php');
}
class hpidl
{
    public $code;
    public $title;
    public $description;
    public $enabled;
    public $hp;
    public $payCode;
    public $tmpStatus;
    
    // class constructor
    public function hpidl()
    {
        global $order, $language;
        
        $this->payCode = 'idl';
        $this->code = 'hp' . $this->payCode;
        $this->title = MODULE_PAYMENT_HPIDL_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_HPIDL_TEXT_DESC;
        $this->sort_order = MODULE_PAYMENT_HPIDL_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_HPIDL_STATUS == 'True') ? true : false);
        $this->info = MODULE_PAYMENT_HPIDL_TEXT_INFO;
        // $this->form_action_url = 'checkout_success.php';
        $this->tmpOrders = false;
        $this->tmpStatus = MODULE_PAYMENT_HPIDL_NEWORDER_STATUS_ID;
        $this->order_status = MODULE_PAYMENT_HPIDL_NEWORDER_STATUS_ID;
        $this->hp = new heidelpay();
        $this->hp->actualPaymethod = strtoupper($this->payCode);
        $this->version = $hp->version;
        /*
         * $this->icons_available = xtc_image(DIR_WS_ICONS . 'cc_amex_small.jpg') . ' ' .
         * xtc_image(DIR_WS_ICONS . 'cc_mastercard_small.jpg') . ' ' .
         * xtc_image(DIR_WS_ICONS . 'cc_visa_small.jpg') . ' ' .
         * xtc_image(DIR_WS_ICONS . 'cc_diners_small.jpg');
         */
        
        if (is_object($order)) {
            $this->update_status();
        }
            
            // OT FIX
        if ($_GET['payment_error'] == 'hpot') {
            global $smarty;
            $error = $this->get_error();
            $smarty->assign('error', htmlspecialchars($error['error']));
        }
    }

    public function update_status()
    {
        global $order;
        
        if (($this->enabled == true) && (( int ) MODULE_PAYMENT_HPIDL_ZONE > 0)) {
            $check_flag = false;
            $check_query = xtc_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_HPIDL_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
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

    public function javascript_validation()
    {
        return false;
    }

    public function selection()
    {
        global $order;
        if (strpos($_SERVER['SCRIPT_FILENAME'], 'checkout_payment') !== false) {
            unset($_SESSION['hpLastData']);
            unset($_SESSION['hpIdealData']);
        }
        // $_SESSION['hpModuleMode'] = 'AFTER';
        
        if (MODULE_PAYMENT_HPIDL_TRANSACTION_MODE == 'LIVE' || strpos(MODULE_PAYMENT_HPIDL_TEST_ACCOUNT, $order->customer['email_address']) !== false) {
            if (MODULE_PAYMENT_HPIDL_MODULE_MODE == 'NOWPF') {
                $content = array(
                        array(
                                'title' => MODULE_PAYMENT_HPIDL_ACCOUTCOUNTRY,
                                'field' => '<select name="hpidl[onlineTransferCountry]" style="width: 200px;"><option selected="true" value="NL">Niederlande</option></select>'
                        ),
                        array(
                                'title' => MODULE_PAYMENT_HPIDL_ACCOUNTBANK,
                                'field' => '<select style="width: 200px;" name="hpidl[onlineTransferInstitute]"><option value="ABN_AMRO">ABN AMRO</option><option value="ABN_AMRO_TEST">ABN AMRO Test</option><option value="FORTIS">Fortis</option><option value="FORTIS_TEST">Fortis Test</option><option value="FRIESLAND_BANK">Friesland Bank</option><option value="ING">ING</option><option value="ING_TEST">ING Test</option><option value="POSTBANK">Postbank</option><option value="RBS_TEST">RBS Test</option><option value="RABOBANK">Rabobank</option><option value="SNS_BANK">SNS Bank</option></select>'
                        ),
                        array(
                                'title' => MODULE_PAYMENT_HPIDL_ACCOUTNUMBER,
                                'field' => '<input autocomplete="off" value="" style="width: 200px;" maxlength="50" name="hpidl[otAccountNumber]" type="TEXT">'
                        ),
                        array(
                                'title' => MODULE_PAYMENT_HPIDL_ACCOUTOWNER,
                                'field' => '<input value="" style="width: 200px;" maxlength="50" name="hpidl[onlineTransferHolder]" type="TEXT">'
                        )
                );
            } else {
                $content = array(
                        array(
                                'title' => '',
                                'field' => ''
                        )
                );
            }
        } else {
            $content = array(
                    array(
                            'title' => '',
                            'field' => MODULE_PAYMENT_HPIDL_DEBUGTEXT
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
        // echo 'HPIDL: '.__FUNCTION__; exit();
        if (MODULE_PAYMENT_HPIDL_TRANSACTION_MODE == 'LIVE' || strpos(MODULE_PAYMENT_HPIDL_TEST_ACCOUNT, $order->customer['email_address']) !== false) {
            $_SESSION['hpModuleMode'] = MODULE_PAYMENT_HPIDL_MODULE_MODE;
            $_SESSION['hpLastPost'] = $_POST;
            $_SESSION['hpIdealData'] = $_POST['hpidl'];
        } else {
            $payment_error_return = 'payment_error=hpidl&error=' . urlencode(MODULE_PAYMENT_HPIDL_DEBUGTEXT);
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
        }
    }

    public function confirmation()
    {
        return false;
    }

    public function process_button()
    {
        global $order;
        $this->hp->rememberOrderData($order);
        return false;
    }

    public function payment_action()
    {
        return true;
    }

    public function before_process()
    {
        return false;
    }

    public function after_process()
    {
        global $order, $xtPrice, $insert_id;
        $this->hp->setOrderStatus($insert_id, $this->order_status);
        $comment = ' ';
        $this->hp->addHistoryComment($insert_id, $comment, $this->order_status);
        $hpIframe = $this->hp->handleDebit($order, $this->payCode, $insert_id);
        return true;
    }

    public function admin_order($oID)
    {
        return false;
    }

    public function get_error()
    {
        global $_GET;
        
        $error = array(
                'title' => MODULE_PAYMENT_HPIDL_TEXT_ERROR,
                'error' => stripslashes(urldecode($_GET['error']))
        );
        
        return $error;
    }

    public function check()
    {
        if (! isset($this->_check)) {
            $check_query = xtc_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_HPIDL_STATUS'");
            $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }

    public function install()
    {
        $this->remove(true);
        
        $groupId = 6;
        $sqlBase = 'INSERT INTO `' . TABLE_CONFIGURATION . '` SET ';
        $prefix = 'MODULE_PAYMENT_HPIDL_';
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
                'configuration_value' => '31HA07BC8142C5A171744B56E61281E5'
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
                'configuration_value' => '1.7'
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
        
        foreach ($inst as $k => $v) {
            $sql = $sqlBase . ' ';
            foreach ($v as $key => $val) {
                $sql .= '`' . addslashes($key) . '` = "' . $val . '", ';
            }
            $sql .= '`sort_order` = "' . $k . '", ';
            $sql .= '`configuration_group_id` = "' . addslashes($groupId) . '", ';
            $sql .= '`date_added` = NOW() ';
            // echo $sql.'<br>';
            xtc_db_query($sql);
        }
    }

    public function remove($install = false)
    {
        xtc_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    public function keys()
    {
        $prefix = 'MODULE_PAYMENT_HPIDL_';
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
        )
        // $prefix.'',
;
    }
}
