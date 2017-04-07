<?php
require('includes/application_top.php');

// create smarty elements
$smarty = new Smarty;
// include boxes
require(DIR_FS_CATALOG.'templates/'.CURRENT_TEMPLATE.'/source/boxes.php');

$breadcrumb->add(NAVBAR_TITLE_1_CHECKOUT_PAYMENT, xtc_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
$breadcrumb->add(NAVBAR_TITLE_2_CHECKOUT_PAYMENT, xtc_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));

require(DIR_WS_INCLUDES.'header.php');

if (empty($_SESSION['language'])) {
    $_SESSION['language'] = 'german';
}
$smarty->assign('language', $_SESSION['language']);

if (@constant('MODULE_PAYMENT_'.strtoupper($_SESSION['payment']).'_DIRECT_MODE') == 'GAMBIOLIGHTBOX') {
    $smarty->assign('LIGHTBOX', gm_get_conf('GM_LIGHTBOX_CHECKOUT'));
    if ($_SESSION['style_edit_mode'] == 'edit') {
        $smarty->assign('STYLE_EDIT', 1);
    } else {
        $smarty->assign('STYLE_EDIT', 0);
    }
}

$smarty->caching = 0;
$smarty->assign('main_content', $_SESSION['HEIDELPAY_IFRAME']);
$smarty->caching = 0;
if (!defined(RM)) {
    $smarty->load_filter('output', 'note');
}
$smarty->display(CURRENT_TEMPLATE.'/index.html');
include('includes/application_bottom.php');
