<?php
if (file_exists(DIR_WS_CLASSES . 'class.heidelpay.php')) {
    include_once(DIR_WS_CLASSES . 'class.heidelpay.php');
} else {
    require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'class.heidelpay.php');
}
class hpbs
{
    public $code;
    public $title;
    public $description;
    public $enabled;
    public $hp;
    public $payCode;
    public $tmpStatus;
    
    // class constructor
    public function hpbs()
    {
        global $order, $language;
        
        $this->payCode = 'bs';
        $this->code = 'hp' . $this->payCode;
        $this->title = MODULE_PAYMENT_HPBS_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_HPBS_TEXT_DESC;
        $this->sort_order = MODULE_PAYMENT_HPBS_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_HPBS_STATUS == 'True') ? true : false);
        $this->info = MODULE_PAYMENT_HPBS_TEXT_INFO;
        // $this->form_action_url = 'checkout_bsccess.php';
        $this->tmpOrders = false;
        $this->tmpStatus = MODULE_PAYMENT_HPBS_NEWORDER_STATUS_ID;
        $this->order_status = MODULE_PAYMENT_HPBS_NEWORDER_STATUS_ID;
        $this->hp = new heidelpay();
        $this->hp->actualPaymethod = strtoupper($this->payCode);
        $this->version = $hp->version;
        
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
        
        if (($this->enabled == true) && (( int ) MODULE_PAYMENT_HPBS_ZONE > 0)) {
            $check_flag = false;
            $check_query = xtc_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_HPBS_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
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
        $info = '<!--BillSAFE start-->
<noscript>  <a title="Ihre Vorteile" href="http://www.billsafe.de/special/payment-info" target="_blank">  <img src="https://images.billsafe.de/image/image/id/191997712fbe" style="border:0"/>
  </a></noscript><a id="billsafeAdvantagesImageLink" title="Ihre Vorteile" href="#" style="display: none;" onclick="openPopup();"><img src="https://images.billsafe.de/image/image/id/191997712fbe" style="border:0"/></a><script type="text/javascript">  var link = document.getElementById(\'billsafeAdvantagesImageLink\');
  link.style.display = \'inline\';  var openPopup = function(){    var myWindow = window.open(\'http://www.billsafe.de/special/payment-info\', \'BillSAFE\', \'width=520,height=600,left=300,top=100,scrollbars=yes\');    myWindow.focus();  };</script><!--BillSAFE end-->';
        
        global $order;
        
        if (! $this->equalAddress($order)) {
            $content = array(
                    array(
                            'title' => '',
                            'field' => MODULE_PAYMENT_HPBS_ADDRESSCHECK
                    )
            );
            return array(
                    'id' => $this->code,
                    'module' => $this->title,
                    'fields' => $content,
                    'description' => $this->info
            );
        }
        
        if (strpos($_SERVER['SCRIPT_FILENAME'], 'checkout_payment') !== false) {
            unset($_SESSION['hpLastData']);
            unset($_SESSION['hpBSData']);
        }
        if ($_SESSION['customers_status']['customers_status_show_price_tax'] == 0 && $_SESSION['customers_status']['customers_status_add_tax_ot'] == 1) {
            $total = $order->info['total'] + $order->info['tax'];
        } else {
            $total = $order->info['total'];
        }
        $total = $total * 100;
        if (MODULE_PAYMENT_HPBS_MIN_AMOUNT > 0 && MODULE_PAYMENT_HPBS_MIN_AMOUNT > $total) {
            return false;
        }
        if (MODULE_PAYMENT_HPBS_MAX_AMOUNT > 0 && MODULE_PAYMENT_HPBS_MAX_AMOUNT < $total) {
            return false;
        }
        
        if (MODULE_PAYMENT_HPBS_TRANSACTION_MODE == 'LIVE' || strpos(MODULE_PAYMENT_HPBS_TEST_ACCOUNT, $order->customer['email_address']) !== false) {
            $sql = 'SELECT * FROM `' . TABLE_CUSTOMERS . '` WHERE `customers_id` = "' . $_SESSION['customer_id'] . '" ';
            $tmp = xtc_db_fetch_array(xtc_db_query($sql));
            
            $content = array(
                    array(
                            'title' => MODULE_PAYMENT_HPBS_INFO,
                            'field' => $info
                    )
            );
        } else {
            $content = array(
                    array(
                            'title' => '',
                            'field' => MODULE_PAYMENT_HPBS_DEBUGTEXT
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

    public function equalAddress($order)
    {
        $diffs = 0;
        foreach ($order->delivery as $k => $v) {
            if ($order->billing[$k] != $v) {
                $diffs ++;
            }
        }
        return $diffs == 0;
    }

    public function pre_confirmation_check()
    {
        global $order;
        // echo 'HPBS: '.__FUNCTION__; exit();
        if (! $this->equalAddress($order)) {
            $payment_error_return = 'payment_error=hpbs&error=' . urlencode(MODULE_PAYMENT_HPBS_ADDRESSCHECK);
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
        } elseif (MODULE_PAYMENT_HPBS_TRANSACTION_MODE == 'LIVE' || strpos(MODULE_PAYMENT_HPBS_TEST_ACCOUNT, $order->customer['email_address']) !== false) {
            $_SESSION['hpModuleMode'] = 'AFTER';
            $_SESSION['hpLastPost'] = $_POST;
            $_SESSION['hpBSData'] = $_POST['hpbs'];
        } else {
            $payment_error_return = 'payment_error=hpbs&error=' . urlencode(MODULE_PAYMENT_HPBS_DEBUGTEXT);
            xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
        }
    }

    public function confirmation()
    {
        $_SESSION['discount_value'] = $GLOBALS['ot_discount']->output[0]['value'];
        $_SESSION['discount_name'] = $GLOBALS['ot_discount']->output[0]['title'];
        $_SESSION['voucher_value'] = $GLOBALS['ot_gv']->output[0]['value'];
        $_SESSION['voucher_name'] = $GLOBALS['ot_gv']->output[0]['title'];
        $_SESSION['coupon_value'] = $GLOBALS['ot_coupon']->output[0]['value'];
        $_SESSION['coupon_name'] = $GLOBALS['ot_coupon']->output[0]['title'];
        $_SESSION['schg_value'] = $GLOBALS['ot_billsafe']->output[0]['value'];
        $_SESSION['schg_name'] = $GLOBALS['ot_billsafe']->output[0]['title'];
        $_SESSION['lofee_value'] = $GLOBALS['ot_loworderfee']->output[0]['value'];
        $_SESSION['lofee_name'] = $GLOBALS['ot_loworderfee']->output[0]['title'];
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
        unset($_SESSION['discount_value']);
        unset($_SESSION['discount_name']);
        unset($_SESSION['voucher_value']);
        unset($_SESSION['voucher_name']);
        unset($_SESSION['coupon_value']);
        unset($_SESSION['coupon_name']);
        unset($_SESSION['schg_value']);
        unset($_SESSION['schg_name']);
        unset($_SESSION['lofee_value']);
        unset($_SESSION['lofee_name']);
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
                'title' => MODULE_PAYMENT_HPBS_TEXT_ERROR,
                'error' => stripslashes(urldecode($_GET['error']))
        );
        
        return $error;
    }

    public function check()
    {
        if (! isset($this->_check)) {
            $check_query = xtc_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_HPBS_STATUS'");
            $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }

    public function install()
    {
        $this->remove(true);
        
        $groupId = 6;
        $sqlBase = 'INSERT INTO `' . TABLE_CONFIGURATION . '` SET ';
        $prefix = 'MODULE_PAYMENT_HPBS_';
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
                'configuration_value' => '31HA07BC8142EE6D02715F4CA97DDD8B'
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
                'configuration_value' => '1.11'
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
        $prefix = 'MODULE_PAYMENT_HPBS_';
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
        )
        // $prefix.'',
;
    }
}
