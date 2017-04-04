<?php
/**
 * heidelpay success action
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
include('includes/application_top.php');
// create smarty elements
$smarty = new Smarty;
// include boxes
require(DIR_FS_CATALOG.'templates/'.CURRENT_TEMPLATE.'/source/boxes.php');

// if the customer is not logged on, redirect them to the shopping cart page
if (!isset($_SESSION['customer_id'])) {
    xtc_redirect(xtc_href_link(FILENAME_SHOPPING_CART));
}

if (isset($_GET['action']) && ($_GET['action'] == 'update')) {
    if ($_SESSION['account_type'] != 1) {
        xtc_redirect(xtc_href_link(FILENAME_DEFAULT));
    } else {
        xtc_redirect(xtc_href_link(FILENAME_LOGOFF));
    }
}
$breadcrumb->add(NAVBAR_TITLE_1_CHECKOUT_SUCCESS);
$breadcrumb->add(NAVBAR_TITLE_2_CHECKOUT_SUCCESS);

require(DIR_WS_INCLUDES.'header.php');

$orders_query = xtc_db_query("select orders_id, orders_status from ".TABLE_ORDERS." 
where customers_id = '".$_SESSION['customer_id']."' order by orders_id desc limit 1");
$orders = xtc_db_fetch_array($orders_query);
$last_order = $orders['orders_id'];
$order_status = $orders['orders_status'];

if ($_SESSION['language'] == 'german') {
    define('HP_SUCCESS_PREPAID', 'Ihre Transaktion war erfolgreich!

&Uuml;berweisen Sie uns den Betrag von {CURRENCY} {AMOUNT} auf folgendes Konto
Land :         {ACC_COUNTRY}
Kontoinhaber : {ACC_OWNER}
Konto-Nr. :    {ACC_NUMBER}
Bankleitzahl:  {ACC_BANKCODE}
IBAN:          {ACC_IBAN}
BIC:           {ACC_BIC}
Geben sie bitte im Verwendungszweck UNBEDINGT die Identifikationsnummer
{SHORTID}
und NICHTS ANDERES an.');
} else {
    define('HP_SUCCESS_PREPAID', 'Your transaction was successfull! 
  
Transfer the amount of {CURRENCY} {AMOUNT} to the following account
Country :         {ACC_COUNTRY}
Account holder :  {ACC_OWNER}
Account No. :     {ACC_NUMBER}
Bank Code:        {ACC_BANKCODE}
IBAN:             {ACC_IBAN}
BIC:              {ACC_BIC}
When you transfer the money you HAVE TO use the identification number
{SHORTID}
as the descriptor and nothing else. Otherwise we cannot match your transaction!');
}

$text = '';
if (!empty($_SESSION['hpPrepaidData'])) {
    $text.= '<br><br>'.nl2br(strtr(HP_SUCCESS_PREPAID, $_SESSION['hpPrepaidData']));
}

$smarty->assign('FORM_ACTION', xtc_draw_form('order',
xtc_href_link(FILENAME_CHECKOUT_SUCCESS, 'action=update', 'SSL')));
$smarty->assign('BUTTON_CONTINUE', xtc_image_submit('button_continue.gif', IMAGE_BUTTON_CONTINUE));
$smarty->assign('BUTTON_PRINT', '<img src="'.'templates/'.CURRENT_TEMPLATE.'/buttons/'.
    $_SESSION['language'].'/button_print.gif" style="cursor:hand" onclick="window.open(\''.xtc_href_link(FILENAME_PRINT_ORDER, 'oID='.$orders['orders_id']).'\', \'popup\', \'toolbar=0, width=640, height=600\')" />'.$text);
$smarty->assign('FORM_END', '</form>');
// GV Code Start
$gv_query = xtc_db_query("select amount from ".TABLE_COUPON_GV_CUSTOMER." where 
customer_id='".$_SESSION['customer_id']."'");
if ($gv_result = xtc_db_fetch_array($gv_query)) {
    if ($gv_result['amount'] > 0) {
        $smarty->assign('GV_SEND_LINK', xtc_href_link(FILENAME_GV_SEND));
    }
}
// GV Code End
// Google Conversion tracking
if (GOOGLE_CONVERSION == 'true') {
    $smarty->assign('google_tracking', 'true');
    $smarty->assign('tracking_code', '
		<noscript>
		<a href="http://services.google.com/sitestats/'.GOOGLE_LANG.'.html" 
		onclick="window.open(this.href); return false;">
		<img height=27 width=135 border=0 src="http://www.googleadservices.com/pagead/conversion/'
        .GOOGLE_CONVERSION_ID.'/?hl='.GOOGLE_LANG.'" />
		</a>
		</noscript>
		    ');
}
if (DOWNLOAD_ENABLED == 'true') {
    include(DIR_WS_MODULES.'downloads.php');
}
$smarty->assign('language', $_SESSION['language']);
$smarty->assign('PAYMENT_BLOCK', $payment_block);
$smarty->caching = 0;
$main_content = $smarty->fetch(CURRENT_TEMPLATE.'/module/checkout_success.html');

$smarty->assign('language', $_SESSION['language']);
$smarty->assign('main_content', $main_content);
$smarty->caching = 0;
if (!defined(RM)) {
    $smarty->load_filter('output', 'note');
}
$smarty->display(CURRENT_TEMPLATE.'/index.html');
include('includes/application_bottom.php');
