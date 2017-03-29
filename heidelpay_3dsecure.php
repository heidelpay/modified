<?php
require('includes/application_top.php');
// create smarty elements
$smarty = new Smarty;
// include boxes
require(DIR_FS_CATALOG.'templates/'.CURRENT_TEMPLATE.'/source/boxes.php');

$breadcrumb->add(NAVBAR_TITLE_1_CHECKOUT_SUCCESS);
$breadcrumb->add(NAVBAR_TITLE_2_CHECKOUT_SUCCESS);

require(DIR_WS_INCLUDES.'header.php');

if (empty($_SESSION['language'])) {
    $_SESSION['language'] = 'german';
}
$smarty->caching = 0;
$main_content = $smarty->fetch(CURRENT_TEMPLATE.'/module/checkout_success.html');

$smarty->assign('language', $_SESSION['language']);
$smarty->assign('main_content', '<center>'.$_SESSION['HEIDELPAY_IFRAME'].'</center>');
$smarty->caching = 0;
if (!defined(RM)) {
    $smarty->load_filter('output', 'note');
}
$smarty->display(CURRENT_TEMPLATE.'/index.html');
include('includes/application_bottom.php');
