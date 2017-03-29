<?php
if (file_exists(DIR_WS_CLASSES . 'class.heidelpay.php')) {
    include_once(DIR_WS_CLASSES . 'class.heidelpay.php');
} else {
    require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'class.heidelpay.php');
}
class hpdd
{
    public $code;
    public $title;
    public $description;
    public $enabled;
    public $hp;
    public $payCode;
    public $tmpStatus;
    
    // class constructor
    public function hpdd()
    {
        global $order, $language;
        
        $this->payCode = 'dd';
        $this->code = 'hp' . $this->payCode;
        $this->title = MODULE_PAYMENT_HPDD_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_HPDD_TEXT_DESC;
        $this->sort_order = MODULE_PAYMENT_HPDD_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_HPDD_STATUS == 'True') ? true : false);
        $this->info = MODULE_PAYMENT_HPDD_TEXT_INFO;
        // $this->form_action_url = 'checkout_success.php';
        $this->tmpOrders = false;
        $this->tmpStatus = MODULE_PAYMENT_HPDD_NEWORDER_STATUS_ID;
        $this->order_status = MODULE_PAYMENT_HPDD_NEWORDER_STATUS_ID;
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
        
        if (($this->enabled == true) && (( int ) MODULE_PAYMENT_HPDD_ZONE > 0)) {
            $check_flag = false;
            $check_query = xtc_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_HPDD_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
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
            unset($_SESSION['hpDDData']);
        }
        if ($_SESSION['customers_status']['customers_status_show_price_tax'] == 0 && $_SESSION['customers_status']['customers_status_add_tax_ot'] == 1) {
            $total = $order->info['total'] + $order->info['tax'];
        } else {
            $total = $order->info['total'];
        }
        $total = $total * 100;
        if (MODULE_PAYMENT_HPDD_MIN_AMOUNT > 0 && MODULE_PAYMENT_HPDD_MIN_AMOUNT > $total) {
            return false;
        }
        if (MODULE_PAYMENT_HPDD_MAX_AMOUNT > 0 && MODULE_PAYMENT_HPDD_MAX_AMOUNT < $total) {
            return false;
        }
        
        $sql = 'SELECT * FROM `' . TABLE_CUSTOMERS . '` WHERE `customers_id` = "' . $_SESSION['customer_id'] . '" ';
        $tmp = xtc_db_fetch_array(xtc_db_query($sql));
        // echo '<pre>'.print_r($tmp, 1).'</pre>';
        // echo '<pre>'.print_r($_SESSION, 1).'</pre>';
        
        if (MODULE_PAYMENT_HPDD_TRANSACTION_MODE == 'LIVE' || strpos(MODULE_PAYMENT_HPDD_TEST_ACCOUNT, $order->customer['email_address']) !== false) {
            $sepamode = strtolower(MODULE_PAYMENT_HPDD_SEPA_MODE);
            
            if (is_int(strpos($sepamode, 'both'))) {
                print "<script type='text/javascript'>
				window.onload = function(){
					var iban_switch = document.getElementById('iban_switch');
					
					var accNr 		= document.getElementsByName('hpdd[AccountNumber]')[0];
					var accBank 	= document.getElementsByName('hpdd[BankCode]')[0];
					var accIban 	= document.getElementsByName('hpdd[AccountIBAN]')[0];
		

					if(iban_switch.value == 'iban'){ iban(); }
					if(iban_switch.value == 'noiban'){ noiban(); }

					iban_switch.onchange = function(){
						if(this.value == 'iban'){ iban(); }
						if(this.value == 'noiban'){ noiban(); }
					}
					
					function iban(){
						accNr.parentNode.parentNode.style.display = 'none';
						accBank.parentNode.parentNode.style.display = 'none';
						accIban.parentNode.parentNode.style.display = 'table-row';
					
					}						
					function noiban(){
						accNr.parentNode.parentNode.style.display = 'table-row';
						accBank.parentNode.parentNode.style.display = 'table-row';
						accIban.parentNode.parentNode.style.display = 'none';
					
					}
				}
			</script>";
                
                $content[] = array(
                        'title' => MODULE_PAYMENT_HPDD_ACCOUNT_SWITCH,
                        'field' => '<select name="hpdd[sepaSwitch]" id="iban_switch" style="width: 200px;"><option value="iban">' . MODULE_PAYMENT_HPDD_ACCOUNT_SWITCH_IBAN . '</option><option value="noiban">' . MODULE_PAYMENT_HPDD_ACCOUNT_SWITCH_CLASSIC . '</option></select>'
                );
            }
            if (is_int(strpos($sepamode, 'account')) || is_int(strpos($sepamode, 'konton')) || is_int(strpos($sepamode, 'both'))) {
                $content[] = array(
                        'title' => MODULE_PAYMENT_HPDD_ACCOUNT_NUMBER,
                        'field' => '<input autocomplete="off" value="" style="width: 200px;" maxlength="50" name="hpdd[AccountNumber]" type="TEXT">'
                );
                $content[] = array(
                        'title' => MODULE_PAYMENT_HPDD_ACCOUNT_BANK,
                        'field' => '<input value="" style="width: 200px;" maxlength="50" name="hpdd[BankCode]" type="TEXT">'
                );
            }
            if (is_int(strpos($sepamode, 'iban')) || is_int(strpos($sepamode, 'both'))) {
                $content[] = array(
                        'title' => MODULE_PAYMENT_HPDD_ACCOUNT_IBAN,
                        'field' => '<input autocomplete="off" value="" style="width: 200px;" maxlength="50" name="hpdd[AccountIBAN]" type="TEXT">'
                );
                /*
                 * $content[] = array (
                 * 'title' => MODULE_PAYMENT_HPDD_ACCOUNT_BIC,
                 * 'field' => '<input value="" style="width: 200px;" maxlength="50" name="hpdd[AccountBIC]" type="TEXT">',
                 * );
                 */
            }
            
            $content[] = array(
                    'title' => MODULE_PAYMENT_HPDD_ACCOUNT_HOLDER,
                    'field' => '<input value="' . $order->customer['firstname'] . ' ' . $order->customer['lastname'] . '" style="width: 200px;" maxlength="50" name="hpdd[Holder]" type="TEXT">'
            );
        } else {
            $content = array(
                    array(
                            'title' => '',
                            'field' => MODULE_PAYMENT_HPDD_DEBUGTEXT
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
        
        /*
         * $_SESSION['hpModuleMode'] = MODULE_PAYMENT_HPDD_MODULE_MODE;
         * if(empty($_POST['hpdd']['AccountNumber']) || empty($_POST['hpdd']['BankCode']) || empty($_POST['hpdd']['Holder'])){
         * $payment_error_return = 'payment_error=hpdd&error='.MODULE_PAYMENT_HPDD_ERROR_NO_PAYDATA;
         * xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
         * } else if($_POST['hpddFirstStep'] == 1 && MODULE_PAYMENT_HPDD_MODULE_MODE == 'DIRECT'){
         * unset($_POST['hpddFirstStep']);
         * $_SESSION['hpLastPost'] = $_POST;
         * $payment_error_return = 'hpddreg=1';
         * xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
         * }else{
         * $_SESSION['hpLastPost'] = $_POST;
         * $_SESSION['hpDDData'] = $_POST['hpdd'];
         * }
         */
        global $order;
        if (MODULE_PAYMENT_HPDD_TRANSACTION_MODE == 'LIVE' || strpos(MODULE_PAYMENT_HPDD_TEST_ACCOUNT, $order->customer['email_address']) !== false) {
            if (count($_POST['hpdd']) > 3) {
                if (($_POST['hpdd']['sepaSwitch'] == 'iban' && (($_POST['hpdd']['AccountIBAN'] == ''))) || ($_POST['hpdd']['sepaSwitch'] == 'noiban' && (($_POST['hpdd']['AccountNumber'] == '') || ($_POST['hpdd']['BankCode'] == ''))) || ($_POST['hpdd']['Holder'] == '')) {
                    $payment_error_return = 'payment_error=hpdd&error=' . urlencode(MODULE_PAYMENT_HPDD_PAYMENT_DATA);
                    xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
                }
            } else {
                foreach ($_POST['hpdd'] as $key => $value) {
                    if ($value == '') {
                        $payment_error_return = 'payment_error=hpdd&error=' . urlencode(MODULE_PAYMENT_HPDD_PAYMENT_DATA);
                        xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
                        break;
                    }
                }
            }
            if ($payment_error_return == '') {
                $_SESSION['hpLastPost'] = $_POST;
                $_SESSION['hpDDData'] = $_POST['hpdd'];
            }
        } else {
            $payment_error_return = 'payment_error=hpdd&error=' . urlencode(MODULE_PAYMENT_HPDD_DEBUGTEXT);
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
                'title' => MODULE_PAYMENT_HPDD_TEXT_ERROR,
                'error' => stripslashes(urldecode($_GET['error']))
        );
        
        return $error;
    }

    public function check()
    {
        if (! isset($this->_check)) {
            $check_query = xtc_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_HPDD_STATUS'");
            $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }

    public function install()
    {
        if (! $this->hp->install_db(TABLE_CUSTOMERS, 'hpdd_kto', 'ALTER TABLE `' . TABLE_CUSTOMERS . '` ADD `hpdd_kto` VARCHAR(50) NOT NULL') || ! $this->hp->install_db(TABLE_CUSTOMERS, 'hpdd_blz', 'ALTER TABLE `' . TABLE_CUSTOMERS . '` ADD `hpdd_blz` VARCHAR(50) NOT NULL') || ! $this->hp->install_db(TABLE_CUSTOMERS, 'hpdd_own', 'ALTER TABLE `' . TABLE_CUSTOMERS . '` ADD `hpdd_own` VARCHAR(50) NOT NULL')) {
            die('Es gab ein Problem bei der Installation des Moduls.');
        }
        
        $this->remove(true);
        
        $groupId = 6;
        $sqlBase = 'INSERT INTO `' . TABLE_CONFIGURATION . '` SET ';
        $prefix = 'MODULE_PAYMENT_HPDD_';
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
                'configuration_key' => $prefix . 'SEPA_MODE',
                'configuration_value' => 'both with selector',
                'set_function' => 'xtc_cfg_select_option(array(\'Account and bank no.\', \'IBAN\', \'both with selector\'), '
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
        
        foreach ($inst as $k => $v) {
            $sql = $sqlBase . ' ';
            foreach ($v as $key => $val) {
                $sql .= '`' . addslashes($key) . '` = "' . $val . '", ';
            }
            $sql .= '`sort_order` = "' . $k . '", ';
            $sql .= '`configuration_group_id` = "' . addslashes($groupId) . '", ';
            $sql .= '`date_added` = NOW() ';
            xtc_db_query($sql);
        }
    }

    public function remove($install = false)
    {
        xtc_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
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
                $prefix . 'SEPA_MODE',
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
