<?php
if (file_exists(DIR_WS_CLASSES . 'class.heidelpay.php')) {
    include_once(DIR_WS_CLASSES . 'class.heidelpay.php');
} else {
    require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'class.heidelpay.php');
}
class hpdc
{
    public $code;
    public $title;
    public $description;
    public $enabled;
    public $hp;
    public $payCode;
    public $tmpStatus;
    
    // class constructor
    public function hpdc()
    {
        global $order, $language;
        
        $this->payCode = 'dc';
        $this->code = 'hp' . $this->payCode;
        $this->title = MODULE_PAYMENT_HPDC_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_HPDC_TEXT_DESC;
        $this->sort_order = MODULE_PAYMENT_HPDC_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_HPDC_STATUS == 'True') ? true : false);
        $this->info = MODULE_PAYMENT_HPDC_TEXT_INFO;
        // $this->form_action_url = 'checkout_success.php';
        $this->tmpOrders = false;
        $this->tmpStatus = MODULE_PAYMENT_HPDC_NEWORDER_STATUS_ID;
        $this->order_status = MODULE_PAYMENT_HPDC_NEWORDER_STATUS_ID;
        $this->hp = new heidelpay();
        $this->hp->actualPaymethod = strtoupper($this->payCode);
        $this->version = $hp->version;
        
        if (is_object($order)) {
            $this->update_status();
        }
    }

    public function update_status()
    {
        global $order;
        
        if (($this->enabled == true) && (( int ) MODULE_PAYMENT_HPDC_ZONE > 0)) {
            $check_flag = false;
            $check_query = xtc_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_HPDC_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
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
            unset($_SESSION['hpUseUniqueId']);
        }
        if ($_SESSION['customers_status']['customers_status_show_price_tax'] == 0 && $_SESSION['customers_status']['customers_status_add_tax_ot'] == 1) {
            $total = $order->info['total'] + $order->info['tax'];
        } else {
            $total = $order->info['total'];
        }
        $total = $total * 100;
        if (MODULE_PAYMENT_HPDC_MIN_AMOUNT > 0 && MODULE_PAYMENT_HPDC_MIN_AMOUNT > $total) {
            return false;
        }
        if (MODULE_PAYMENT_HPDC_MAX_AMOUNT > 0 && MODULE_PAYMENT_HPDC_MAX_AMOUNT < $total) {
            return false;
        }
        
        $src = '';
        $src = $this->hp->handleRegister($order, $this->payCode);
        
        $hpIframe = '';
        if (! empty($src)) {
            $hpIframe = '<iframe src="' . $src . '" frameborder="0" width="400" height="250"></iframe>';
            $hpIframe .= '<script>
        function switchFrameDC(val)
        {
          var objLast = document.getElementById("lastDCard");
          var objNew = document.getElementById("newDCard");
          if(val.checked){
            objLast.style.display = "block";
            objNew.style.display = "none";
          }else{
            objLast.style.display = "none";
            objNew.style.display = "block";
          }
        }
      </script>';
        }
        
        if (MODULE_PAYMENT_HPDC_TRANSACTION_MODE == 'LIVE' || strpos(MODULE_PAYMENT_HPDC_TEST_ACCOUNT, $order->customer['email_address']) !== false) {
            $content = array();
            if (MODULE_PAYMENT_HPDC_MODULE_MODE == 'DIRECT') {
                // Special DC Reuse
                $lastDCard = $this->hp->loadMEMO($_SESSION['customer_id'], 'heidelpay_last_debitcard');
                // if(!empty($lastDCard)){
                $gender = $_SESSION['customer_gender'] == 'f' ? FEMALE : MALE;
                $name = $_SESSION['customer_last_name'];
                if (! empty($lastDCard)) {
                    $title = $gender . ' ' . $name . ', ' . MODULE_PAYMENT_HPDC_REUSE_CARD . '<br>' . '<div id="lastDCard">' . MODULE_PAYMENT_HPDC_REUSE_CARD_NUMBER . $lastDCard . '</div>' . '<input type="hidden" name="hpdcUniqueId" id="hpdcUniqueId" value="">' . '<input type="checkbox" name="hpdcReuseCard" value="1" checked onChange="switchFrameDC(this)">' . MODULE_PAYMENT_HPDC_REUSE_CARD_TEXT . '<div id="newDCard" style="display: none">' . $hpIframe . '</div>';
                } else {
                    $title = '<div id="newDCard" style="display: block">' . $hpIframe . '</div>' . '<input type="hidden" name="hpdcUniqueId" id="hpdcUniqueId" value="">';
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
                            'field' => MODULE_PAYMENT_HPDC_DEBUGTEXT
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
        // echo 'HPDC: '.__FUNCTION__; exit();
        if (MODULE_PAYMENT_HPDC_TRANSACTION_MODE == 'LIVE' || strpos(MODULE_PAYMENT_HPDC_TEST_ACCOUNT, $order->customer['email_address']) !== false) {
            $_SESSION['hpModuleMode'] = MODULE_PAYMENT_HPDC_MODULE_MODE;
            if ($_POST['hpdcReuseCard'] == 1) {
                $uniqueID = $this->hp->loadMEMO($_SESSION['customer_id'], 'heidelpay_last_debitcard_reference');
                if (! empty($uniqueID)) {
                    $_SESSION['hpUseUniqueId'] = $uniqueID;
                }
            } elseif ($_POST['hpdcUniqueId'] != '') {
                $uniqueID = $_POST['hpdcUniqueId'];
                if (! empty($uniqueID)) {
                    $_SESSION['hpUseUniqueId'] = $uniqueID;
                }
            } elseif (MODULE_PAYMENT_HPDC_MODULE_MODE == 'DIRECT') {
                $payment_error_return = 'payment_error=hpdc&error=' . MODULE_PAYMENT_HPDC_ERROR_NO_PAYDATA;
                xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
            }
            if (! isset($_SESSION['hpUseUniqueId']) && $_POST['hpdcFirstStep'] == 1 && MODULE_PAYMENT_HPDC_MODULE_MODE == 'DIRECT') {
                unset($_POST['hpdcFirstStep']);
                $_SESSION['hpLastPost'] = $_POST;
                $payment_error_return = 'hpdcreg=1';
                xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
            } else {
                $_SESSION['hpLastPost'] = $_POST;
            }
        } else {
            $payment_error_return = 'payment_error=hpdc&error=' . urlencode(MODULE_PAYMENT_HPDC_DEBUGTEXT);
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
        }
    }

    public function confirmation()
    {
        if (! isset($_SESSION['hpUseUniqueId'])) {
            return false;
        }
            // Special DC Reuse
        $lastDCard = $this->hp->loadMEMO($_SESSION['customer_id'], 'heidelpay_last_debitcard');
        $content = array();
        if (! empty($lastDCard)) {
            $gender = $_SESSION['customer_gender'] == 'f' ? FEMALE : MALE;
            $name = $_SESSION['customer_last_name'];
            $content[] = array(
                    'title' => MODULE_PAYMENT_HPDC_WILLUSE_CARD . $lastDCard,
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
                'title' => MODULE_PAYMENT_HPDC_TEXT_ERROR,
                'error' => stripslashes(urldecode($_GET['error']))
        );
        
        return $error;
    }

    public function check()
    {
        if (! isset($this->_check)) {
            $check_query = xtc_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_HPDC_STATUS'");
            $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }

    public function install()
    {
        $this->remove(true);
        
        $groupId = 6;
        $sqlBase = 'INSERT INTO `' . TABLE_CONFIGURATION . '` SET ';
        $prefix = 'MODULE_PAYMENT_HPDC_';
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
                'configuration_value' => '1.2'
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
        $prefix = 'MODULE_PAYMENT_HPDC_';
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
                $prefix . 'ZONE'
        )
        // $prefix.'',
;
    }
}
