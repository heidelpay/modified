<?php
require('includes/application_top.php');

// create smarty elements
$smarty = new Smarty;
// include boxes
require(DIR_FS_CATALOG.'templates/'.CURRENT_TEMPLATE.'/source/boxes.php');

$breadcrumb->add(NAVBAR_TITLE_1_CHECKOUT_PAYMENT, xtc_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
$breadcrumb->add(NAVBAR_TITLE_2_CHECKOUT_PAYMENT, xtc_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));

require(DIR_WS_INCLUDES.'header.php');

$smarty->assign('LIGHTBOX', gm_get_conf('GM_LIGHTBOX_CHECKOUT'));
if ($_SESSION['style_edit_mode'] == 'edit') {
    $smarty->assign('STYLE_EDIT', 1);
} else {
    $smarty->assign('STYLE_EDIT', 0);
}
$smarty->assign('language', $_SESSION['language']);

$smarty->caching = 0;
$main_content = $smarty->fetch(CURRENT_TEMPLATE.'/module/checkout_payment.html');
$smarty->assign('main_content', $_SESSION['HEIDELPAY_IFRAME']);
$smarty->caching = 0;
if (!defined(RM)) {
    $smarty->load_filter('output', 'note');
}
$smarty->display(CURRENT_TEMPLATE.'/index.html');
include('includes/application_bottom.php');
