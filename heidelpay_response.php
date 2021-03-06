<?php
/**
 * heidelpay response action
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  https://dev.heidelpay.com/modified/
 *
 * @package  heidelpay
 * @subpackage modified
 * @category modified
 */
require('includes/application_top.php');

error_reporting(0);

$returnvalue = $_POST['PROCESSING_RESULT'];
if ($returnvalue) {
    include_once(DIR_WS_CLASSES . 'class.heidelpay.php');
    $hp = new heidelpay();
    $hp->trackStep('response', 'post', $_POST);
    $hp->trackStep('response', 'session', $_SESSION);
    $params = '';
    if ($_POST['PAYMENT_CODE'] == 'PP.PA') {
        $params = '&pcode=' . $_POST['PAYMENT_CODE'] . '&';
        foreach ($hp->importantPPFields as $k => $v) {
            $params .= $v . '=' . $_POST[$v] . '&';
        }
    } else {
        $params .= '&code=' . $_POST['PAYMENT_CODE'];
    }
    $payType = substr(strtolower($_POST['PAYMENT_CODE']), 0, 2);
    if ($payType == 'va') {
        $payType = 'ppal';
    } // PayPal special
    $payCode = strtoupper($payType);
    if ($payType == 'ot') {
        $payType = strtolower($hp->getPayCodeByChannel($_POST['TRANSACTION_CHANNEL']));
    }
    $TID = str_replace(' ', '', $_POST['IDENTIFICATION_TRANSACTIONID']);
    $orderID = (int)preg_replace('/User.*Order(\d*)/', '$1', $TID);
    $customerID = $_POST['IDENTIFICATION_SHOPPERID'];


    $comment = 'ShortID: ' . $_POST['IDENTIFICATION_SHORTID'];

    if (isset($_POST['ACCOUNT_HOLDER']) && ($_POST['ACCOUNT_HOLDER'] != '')) {
        $comment .= '; AccountHolder: ' . $_POST['ACCOUNT_HOLDER'];
    }


    $hp->saveIds($_POST['IDENTIFICATION_UNIQUEID'], $orderID, 'hp' . $payType, $_POST['IDENTIFICATION_SHORTID']);
    $base = HTTPS_SERVER . DIR_WS_CATALOG;
    if (strstr($returnvalue, "ACK")) {
        if (strpos($_POST['PAYMENT_CODE'], 'RG') === false) {
            if (!empty($_SESSION['heidel_last_coupon'])) {
                // unset used coupon code on success
                unset($_SESSION['heidel_last_coupon']);
            }
            if (!empty($_SESSION['cc_id'])) {
                // unset last coupon id on success
                unset($_SESSION['cc_id']);
            }
        }
        if (strpos($_POST['PAYMENT_CODE'], 'RG') === false) {
            $status = constant('MODULE_PAYMENT_HP' . $payCode . '_PROCESSED_STATUS_ID');
            $hp->addHistoryComment($orderID, $comment, $status);
            $hp->setOrderStatus($orderID, $status);
        } else {
            $status = constant('MODULE_PAYMENT_HP' . $payCode . '_PENDING_STATUS_ID');
            $hp->addHistoryComment($orderID, $comment, $status);
            $hp->setOrderStatus($orderID, $status);
        }


        if (MODULE_PAYMENT_HPCC_SAVE_REGISTER == 'True' &&
            $_POST['PAYMENT_CODE'] == 'CC.RG' &&
            $_POST['ACCOUNT_NUMBER'] != ''
        ) {
            // credit card registration
            $hp->saveMEMO($customerID, 'heidelpay_last_ccard', $_POST['ACCOUNT_NUMBER']);
            $hp->saveMEMO($customerID, 'heidelpay_last_ccard_reference', $_POST['IDENTIFICATION_UNIQUEID']);
        } elseif (MODULE_PAYMENT_HPDC_SAVE_REGISTER == 'True'
            && $_POST['PAYMENT_CODE'] == 'DC.RG'
            && $_POST['ACCOUNT_NUMBER'] != ''
        ) {
            // debit card registration
            $hp->saveMEMO($customerID, 'heidelpay_last_debitcard', $_POST['ACCOUNT_NUMBER']);
            $hp->saveMEMO($customerID, 'heidelpay_last_debitcard_reference', $_POST['IDENTIFICATION_UNIQUEID']);
        }

        if ($_POST['PROCESSING_STATUS_CODE'] == '90' && $_POST['AUTHENTICATION_TYPE'] == '3DSecure') {
            print $base . "heidelpay_3dsecure_return.php?order_id="
                . rawurlencode($_POST['IDENTIFICATION_TRANSACTIONID']) . '&' . session_name() . '=' . session_id();
        } elseif ($_POST['PAYMENT_CODE'] == 'CC.RG' || $_POST['PAYMENT_CODE'] == 'DC.RG') {
            print $base . "heidelpay_after_register.php?order_id="
                . rawurlencode($_POST['IDENTIFICATION_TRANSACTIONID']) . '&uniqueId='
                . $_POST['IDENTIFICATION_UNIQUEID'] . $params . '&' . session_name() . '=' . session_id();
        } else {
            print $base . "heidelpay_redirect.php?order_id="
                . rawurlencode($_POST['IDENTIFICATION_TRANSACTIONID']) . '&uniqueId='
                . $_POST['IDENTIFICATION_UNIQUEID'] . $params . '&' . session_name() . '=' . session_id();
        }
    } elseif ($_POST['FRONTEND_REQUEST_CANCELLED'] == 'true') {
        $status = constant('MODULE_PAYMENT_HP' . $payCode . '_CANCELED_STATUS_ID');
        $comment .= ' Cancelled by User';
        $hp->addHistoryComment($orderID, $comment, $status);
        $hp->setOrderStatus($orderID, $status);
        $hp->deleteCoupon($orderID);
        print $base . "heidelpay_redirect.php?payment_error=hp"
            . $payType . "&error=" .urlencode($_POST['PROCESSING_RETURN_CODE']). '&' . session_name() . '=' . session_id();
    } else {
        $status = constant('MODULE_PAYMENT_HP' . $payCode . '_CANCELED_STATUS_ID');
        $comment .= ' ' . $_POST['PROCESSING_RETURN'];
        $hp->addHistoryComment($orderID, $comment, $status);
        $hp->setOrderStatus($orderID, $status);
        $hp->deleteCoupon($orderID);
        print $base . "heidelpay_redirect.php?payment_error=hp"
            . $payType . "&error=" . urlencode($_POST['PROCESSING_RETURN_CODE']) . '&' . session_name() . '=' . session_id();
    }
} else {
    echo 'FAIL';
}
