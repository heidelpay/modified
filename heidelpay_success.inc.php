<?php
if (strpos($orders['payment_class'], 'hp') !== false) {
    require_once(DIR_WS_CLASSES.'order.php');
    $order = new order($last_order);
    $insert_id = $last_order;
        
    // BarPay Special
    if ($orders['payment_class'] == 'hpbp') {
        require_once(DIR_WS_CLASSES.'class.heidelpay.php');
        require_once(DIR_WS_LANGUAGES.$_SESSION['language'].'/modules/payment/hpbp.php');
        $barpayComment = heidelpay::getHistoryComment($insert_id, 'Download Link: ');
        $barpayLink = preg_replace('/Download Link: /', '', $barpayComment);
        $HPOUTPUT = preg_replace('/{LINK}/', $barpayLink, MODULE_PAYMENT_HPBP_BARPAY_DOWNLOAD);
        $ori_comment = $orders['comments'];
        heidelpay::saveOrderComment($insert_id, $orders['comments'].'<br><br>BarPay '.$barpayComment);
        include('send_order.php');
        heidelpay::saveOrderComment($insert_id, $ori_comment);
    } elseif (in_array($orders['payment_class'], array('hpiv', 'hppp', 'hpbs', 'hpdd'))) {
        require_once(DIR_WS_CLASSES.'class.heidelpay.php');
        $payComment = heidelpay::getHistoryComment($insert_id, 'Payment Info: ');
        $HPOUTPUT = '<br><br>'.nl2br(preg_replace('/Payment Info: /', '', $payComment));
        //$ori_comment = $orders['comments'];
        //heidelpay::saveOrderComment($insert_id, $orders['comments'].'<br><br>'.$payComment);
        include('send_order.php');
        //heidelpay::saveOrderComment($insert_id, $ori_comment);
        $smarty->assign('NOTIFY_COMMENTS', $HPOUTPUT);
        $smarty->assign('ORDER_STATUS', 'waiting');
        $html_mail = $smarty->fetch(CURRENT_TEMPLATE.'/admin/mail/'.$_SESSION['language'].'/change_order_mail.html');
        $txt_mail = $smarty->fetch(CURRENT_TEMPLATE.'/admin/mail/'.$_SESSION['language'].'/change_order_mail.txt');
        $order_subject = 'Zahlungsinformationen Bestellung '.$insert_id;
        if ($_SESSION['language'] != 'german') {
            $order_subject = 'Paymentinformation Order '.$insert_id;
        }
        // send mail to admin
        xtc_php_mail(EMAIL_BILLING_ADDRESS, EMAIL_BILLING_NAME, EMAIL_BILLING_ADDRESS, STORE_NAME, EMAIL_BILLING_FORWARDING_STRING, $order->customer['email_address'], $order->customer['firstname'], '', '', $order_subject, $html_mail, $txt_mail);
        // send mail to customer
        xtc_php_mail(EMAIL_BILLING_ADDRESS, EMAIL_BILLING_NAME, $order->customer['email_address'], $order->customer['firstname'].' '.$order->customer['lastname'], '', EMAIL_BILLING_REPLY_ADDRESS, EMAIL_BILLING_REPLY_ADDRESS_NAME, '', '', $order_subject, $html_mail, $txt_mail);
    } else {
        include('send_order.php');
    }
}
