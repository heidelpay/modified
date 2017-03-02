<?php
if (file_exists ( DIR_WS_CLASSES . 'class.heidelpay.php' )) {
	include_once (DIR_WS_CLASSES . 'class.heidelpay.php');
} else {
	require_once (DIR_FS_CATALOG . DIR_WS_CLASSES . 'class.heidelpay.php');
}
class hpgp {
	var $code, $title, $description, $enabled, $hp, $payCode, $tmpStatus;
	
	// class constructor
	function hpgp() {
		global $order, $language;
		
		$this->payCode = 'gp';
		$this->code = 'hp' . $this->payCode;
		$this->title = MODULE_PAYMENT_HPGP_TEXT_TITLE;
		$this->description = MODULE_PAYMENT_HPGP_TEXT_DESC;
		$this->sort_order = MODULE_PAYMENT_HPGP_SORT_ORDER;
		$this->enabled = ((MODULE_PAYMENT_HPGP_STATUS == 'True') ? true : false);
		$this->info = MODULE_PAYMENT_HPGP_TEXT_INFO;
		// $this->form_action_url = 'checkout_success.php';
		$this->tmpOrders = false;
		$this->tmpStatus = MODULE_PAYMENT_HPGP_NEWORDER_STATUS_ID;
		$this->order_status = MODULE_PAYMENT_HPGP_NEWORDER_STATUS_ID;
		$this->hp = new heidelpay ();
		$this->hp->actualPaymethod = strtoupper ( $this->payCode );
		$this->version = $hp->version;
		/*
		 * $this->icons_available = xtc_image(DIR_WS_ICONS . 'cc_amex_small.jpg') . ' ' .
		 * xtc_image(DIR_WS_ICONS . 'cc_mastercard_small.jpg') . ' ' .
		 * xtc_image(DIR_WS_ICONS . 'cc_visa_small.jpg') . ' ' .
		 * xtc_image(DIR_WS_ICONS . 'cc_diners_small.jpg');
		 */
		
		if (is_object ( $order ))
			$this->update_status ();
			
			// OT FIX
		if ($_GET ['payment_error'] == 'hpot') {
			GLOBAL $smarty;
			$error = $this->get_error ();
			$smarty->assign ( 'error', htmlspecialchars ( $error ['error'] ) );
		}
	}
	function update_status() {
		global $order;
		
		if (($this->enabled == true) && (( int ) MODULE_PAYMENT_HPGP_ZONE > 0)) {
			$check_flag = false;
			$check_query = xtc_db_query ( "select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_HPGP_ZONE . "' and zone_country_id = '" . $order->billing ['country'] ['id'] . "' order by zone_id" );
			while ( $check = xtc_db_fetch_array ( $check_query ) ) {
				if ($check ['zone_id'] < 1) {
					$check_flag = true;
					break;
				} elseif ($check ['zone_id'] == $order->billing ['zone_id']) {
					$check_flag = true;
					break;
				}
			}
			
			if ($check_flag == false) {
				$this->enabled = false;
			}
		}
	}
	function javascript_validation() {
		return false;
	}
	function selection() {
		GLOBAL $order;
		if (strpos ( $_SERVER ['SCRIPT_FILENAME'], 'checkout_payment' ) !== false) {
			unset ( $_SESSION ['hpLastData'] );
			unset ( $_SESSION ['hpGPData'] );
		}
		if ($_SESSION ['customers_status'] ['customers_status_show_price_tax'] == 0 && $_SESSION ['customers_status'] ['customers_status_add_tax_ot'] == 1) {
			$total = $order->info ['total'] + $order->info ['tax'];
		} else {
			$total = $order->info ['total'];
		}
		$total = $total * 100;
		if (MODULE_PAYMENT_HPGP_MIN_AMOUNT > 0 && MODULE_PAYMENT_HPGP_MIN_AMOUNT > $total)
			return false;
		if (MODULE_PAYMENT_HPGP_MAX_AMOUNT > 0 && MODULE_PAYMENT_HPGP_MAX_AMOUNT < $total)
			return false;
		
		if (MODULE_PAYMENT_HPGP_TRANSACTION_MODE == 'LIVE' || strpos ( MODULE_PAYMENT_HPGP_TEST_ACCOUNT, $order->customer ['email_address'] ) !== false) {
			$sql = 'SELECT * FROM `' . TABLE_CUSTOMERS . '` WHERE `customers_id` = "' . $_SESSION ['customer_id'] . '" ';
			$tmp = xtc_db_fetch_array ( xtc_db_query ( $sql ) );
			
			$content = array (
					array (
							'title' => MODULE_PAYMENT_HPGP_ACCOUNT_IBAN,
							'field' => '<input value="' . $tmp ['hpgp_kto'] . '" style="width: 200px;" maxlength="50" name="hpgp[AccountIBAN]" type="TEXT">' 
					),
					array (
							'title' => MODULE_PAYMENT_HPGP_ACCOUNT_BIC,
							'field' => '<input value="' . $tmp ['hpgp_blz'] . '" style="width: 200px;" maxlength="50" name="hpgp[AccountBIC]" type="TEXT">' 
					),
					array (
							'title' => MODULE_PAYMENT_HPGP_ACCOUNT_OWNER,
							'field' => '<input value="' . $tmp ['hpgp_own'] . '" style="width: 200px;" maxlength="50" name="hpgp[Holder]" type="TEXT">' 
					) 
			);
		} else {
			$content = array (
					array (
							'title' => '',
							'field' => MODULE_PAYMENT_HPGP_DEBUGTEXT 
					) 
			);
		}
		
		return array (
				'id' => $this->code,
				'module' => $this->title,
				'fields' => $content,
				'description' => $this->info 
		);
	}
	function pre_confirmation_check() {
		GLOBAL $order;
		if (MODULE_PAYMENT_HPGP_TRANSACTION_MODE == 'LIVE' || strpos ( MODULE_PAYMENT_HPGP_TEST_ACCOUNT, $order->customer ['email_address'] ) !== false) {
			if (empty ( $_POST ['hpgp'] ['AccountIBAN'] ) || empty ( $_POST ['hpgp'] ['AccountBIC'] ) || empty ( $_POST ['hpgp'] ['Holder'] )) {
				$payment_error_return = 'payment_error=hpgp&error=' . MODULE_PAYMENT_HPGP_ERROR_NO_PAYDATA;
				xtc_redirect ( xtc_href_link ( FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false ) );
			} else {
				$_SESSION ['hpModuleMode'] = MODULE_PAYMENT_HPGP_MODULE_MODE;
				$_SESSION ['hpLastPost'] = $_POST;
				$_SESSION ['hpGPData'] = $_POST ['hpgp'];
			}
		} else {
			$payment_error_return = 'payment_error=hpgp&error=' . urlencode ( MODULE_PAYMENT_HPGP_DEBUGTEXT );
			xtc_redirect ( xtc_href_link ( FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false ) );
		}
	}
	function confirmation() {
		return false;
	}
	function process_button() {
		global $order;
		$this->hp->rememberOrderData ( $order );
		return false;
	}
	function payment_action() {
		return true;
	}
	function before_process() {
		return false;
	}
	function after_process() {
		global $order, $xtPrice, $insert_id;
		$this->hp->setOrderStatus ( $insert_id, $this->order_status );
		$comment = ' ';
		$this->hp->addHistoryComment ( $insert_id, $comment, $this->order_status );
		$hpIframe = $this->hp->handleDebit ( $order, $this->payCode, $insert_id );
		return true;
	}
	function admin_order($oID) {
		return false;
	}
	function get_error() {
		global $_GET;
		
		$error = array (
				'title' => MODULE_PAYMENT_HPGP_TEXT_ERROR,
				'error' => stripslashes ( urldecode ( $_GET ['error'] ) ) 
		);
		
		return $error;
	}
	function check() {
		if (! isset ( $this->_check )) {
			$check_query = xtc_db_query ( "select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_HPGP_STATUS'" );
			$this->_check = xtc_db_num_rows ( $check_query );
		}
		return $this->_check;
	}
	function install() {
		if (! $this->hp->install_db ( TABLE_CUSTOMERS, 'hpgp_kto', 'ALTER TABLE `' . TABLE_CUSTOMERS . '` ADD `hpgp_kto` VARCHAR(50) NOT NULL' ) || ! $this->hp->install_db ( TABLE_CUSTOMERS, 'hpgp_blz', 'ALTER TABLE `' . TABLE_CUSTOMERS . '` ADD `hpgp_blz` VARCHAR(50) NOT NULL' ) || ! $this->hp->install_db ( TABLE_CUSTOMERS, 'hpgp_own', 'ALTER TABLE `' . TABLE_CUSTOMERS . '` ADD `hpgp_own` VARCHAR(50) NOT NULL' ))
			die ( 'Es gab ein Problem bei der Installation des Moduls.' );
		
		$this->remove ( true );
		
		$groupId = 6;
		$sqlBase = 'INSERT INTO `' . TABLE_CONFIGURATION . '` SET ';
		$prefix = 'MODULE_PAYMENT_HPGP_';
		$inst = array ();
		$inst [] = array (
				'configuration_key' => $prefix . 'STATUS',
				'configuration_value' => 'True',
				'set_function' => 'xtc_cfg_select_option(array(\'True\', \'False\'), ' 
		);
		$inst [] = array (
				'configuration_key' => $prefix . 'SECURITY_SENDER',
				'configuration_value' => '31HA07BC8142C5A171745D00AD63D182' 
		);
		$inst [] = array (
				'configuration_key' => $prefix . 'USER_LOGIN',
				'configuration_value' => '31ha07bc8142c5a171744e5aef11ffd3' 
		);
		$inst [] = array (
				'configuration_key' => $prefix . 'USER_PWD',
				'configuration_value' => '93167DE7' 
		);
		$inst [] = array (
				'configuration_key' => $prefix . 'TRANSACTION_CHANNEL',
				'configuration_value' => '31HA07BC8142C5A171740166AF277E03' 
		);
		$inst [] = array (
				'configuration_key' => $prefix . 'TRANSACTION_MODE',
				'configuration_value' => 'TEST',
				'set_function' => 'xtc_cfg_select_option(array(\'LIVE\', \'TEST\'), ' 
		);
		$inst [] = array (
				'configuration_key' => $prefix . 'TEST_ACCOUNT',
				'configuration_value' => '' 
		);
		$inst [] = array (
				'configuration_key' => $prefix . 'SORT_ORDER',
				'configuration_value' => '1.4' 
		);
		$inst [] = array (
				'configuration_key' => $prefix . 'ZONE',
				'configuration_value' => '',
				'set_function' => 'xtc_cfg_pull_down_zone_classes(',
				'use_function' => 'xtc_get_zone_class_title' 
		);
		$inst [] = array (
				'configuration_key' => $prefix . 'ALLOWED',
				'configuration_value' => '' 
		);
		$inst [] = array (
				'configuration_key' => $prefix . 'MIN_AMOUNT',
				'configuration_value' => '' 
		);
		$inst [] = array (
				'configuration_key' => $prefix . 'MAX_AMOUNT',
				'configuration_value' => '' 
		);
		$inst [] = array (
				'configuration_key' => $prefix . 'PROCESSED_STATUS_ID',
				'configuration_value' => '0',
				'set_function' => 'xtc_cfg_pull_down_order_statuses(',
				'use_function' => 'xtc_get_order_status_name' 
		);
		$inst [] = array (
				'configuration_key' => $prefix . 'PENDING_STATUS_ID',
				'configuration_value' => '0',
				'set_function' => 'xtc_cfg_pull_down_order_statuses(',
				'use_function' => 'xtc_get_order_status_name' 
		);
		$inst [] = array (
				'configuration_key' => $prefix . 'CANCELED_STATUS_ID',
				'configuration_value' => '0',
				'set_function' => 'xtc_cfg_pull_down_order_statuses(',
				'use_function' => 'xtc_get_order_status_name' 
		);
		$inst [] = array (
				'configuration_key' => $prefix . 'NEWORDER_STATUS_ID',
				'configuration_value' => '0',
				'set_function' => 'xtc_cfg_pull_down_order_statuses(',
				'use_function' => 'xtc_get_order_status_name' 
		);
		$inst [] = array (
				'configuration_key' => $prefix . 'DEBUG',
				'configuration_value' => 'False',
				'set_function' => 'xtc_cfg_select_option(array(\'True\', \'False\'), ' 
		);
		
		foreach ( $inst as $k => $v ) {
			$sql = $sqlBase . ' ';
			foreach ( $v as $key => $val ) {
				$sql .= '`' . addslashes ( $key ) . '` = "' . $val . '", ';
			}
			$sql .= '`sort_order` = "' . $k . '", ';
			$sql .= '`configuration_group_id` = "' . addslashes ( $groupId ) . '", ';
			$sql .= '`date_added` = NOW() ';
			// echo $sql.'<br>';
			xtc_db_query ( $sql );
		}
	}
	function remove($install = false) {
		xtc_db_query ( "delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode ( "', '", $this->keys () ) . "')" );
	}
	function keys() {
		$prefix = 'MODULE_PAYMENT_HPGP_';
		return array (
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
?>
