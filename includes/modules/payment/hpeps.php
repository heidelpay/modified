<?php
if (file_exists ( DIR_WS_CLASSES . 'class.heidelpay.php' )) {
	include_once (DIR_WS_CLASSES . 'class.heidelpay.php');
} else {
	require_once (DIR_FS_CATALOG . DIR_WS_CLASSES . 'class.heidelpay.php');
}
class hpeps {
	var $code, $title, $description, $enabled, $hp, $payCode, $tmpStatus;
	
	// class constructor
	function hpeps() {
		global $order, $language;
		
		$this->payCode = 'eps';
		$this->code = 'hp' . $this->payCode;
		$this->title = MODULE_PAYMENT_HPEPS_TEXT_TITLE;
		$this->description = MODULE_PAYMENT_HPEPS_TEXT_DESC;
		$this->sort_order = MODULE_PAYMENT_HPEPS_SORT_ORDER;
		$this->enabled = ((MODULE_PAYMENT_HPEPS_STATUS == 'True') ? true : false);
		$this->info = MODULE_PAYMENT_HPEPS_TEXT_INFO;
		// $this->form_action_url = 'checkout_success.php';
		$this->tmpOrders = false;
		$this->tmpStatus = MODULE_PAYMENT_HPEPS_NEWORDER_STATUS_ID;
		$this->order_status = MODULE_PAYMENT_HPEPS_NEWORDER_STATUS_ID;
		$this->hp = new heidelpay ();
		$this->hp->actualPaymethod = strtoupper ( $this->payCode );
		$this->version = $hp->version;
		
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
		
		if (($this->enabled == true) && (( int ) MODULE_PAYMENT_HPEPS_ZONE > 0)) {
			$check_flag = false;
			$check_query = xtc_db_query ( "select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_HPEPS_ZONE . "' and zone_country_id = '" . $order->billing ['country'] ['id'] . "' order by zone_id" );
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
		}
		// $_SESSION['hpModuleMode'] = 'AFTER';
		
		if (MODULE_PAYMENT_HPEPS_TRANSACTION_MODE == 'LIVE' || strpos ( MODULE_PAYMENT_HPEPS_TEST_ACCOUNT, $order->customer ['email_address'] ) !== false) {
			$content = array (
					array (
							'title' => '',
							'field' => '' 
					) 
			);
		} else {
			$content = array (
					array (
							'title' => '',
							'field' => MODULE_PAYMENT_HPEPS_DEBUGTEXT 
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
		// echo 'HPEPS: '.__FUNCTION__; exit();
		if (MODULE_PAYMENT_HPEPS_TRANSACTION_MODE == 'LIVE' || strpos ( MODULE_PAYMENT_HPEPS_TEST_ACCOUNT, $order->customer ['email_address'] ) !== false) {
			$_SESSION ['hpModuleMode'] = 'AFTER';
			$_SESSION ['hpLastPost'] = $_POST;
		} else {
			$payment_error_return = 'payment_error=hpeps&error=' . urlencode ( MODULE_PAYMENT_HPEPS_DEBUGTEXT );
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
				'title' => MODULE_PAYMENT_HPEPS_TEXT_ERROR,
				'error' => stripslashes ( urldecode ( $_GET ['error'] ) ) 
		);
		
		return $error;
	}
	function check() {
		if (! isset ( $this->_check )) {
			$check_query = xtc_db_query ( "select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_HPEPS_STATUS'" );
			$this->_check = xtc_db_num_rows ( $check_query );
		}
		return $this->_check;
	}
	function install() {
		$this->remove ( true );
		
		$groupId = 6;
		$sqlBase = 'INSERT INTO `' . TABLE_CONFIGURATION . '` SET ';
		$prefix = 'MODULE_PAYMENT_HPEPS_';
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
				'configuration_value' => '' 
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
				'configuration_value' => '1.8' 
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
		$prefix = 'MODULE_PAYMENT_HPEPS_';
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
