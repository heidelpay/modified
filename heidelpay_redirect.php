<?php

require('includes/application_top.php');

$base = HTTPS_SERVER.DIR_WS_CATALOG;
$debug = false;
if ($debug) {
    echo '#<pre>'.print_r($_SESSION, 1).'</pre>';
}

include_once(DIR_WS_CLASSES.'class.heidelpay.php');
$hp = new heidelpay();
$hp->trackStep('redirect', 'post', $_POST);
$hp->trackStep('redirect', 'session', $_SESSION);

$_POST = $_SESSION['hpLastPost'];
$_POST['blabla'] = 1; // AGBs leer
if (isset($_SESSION['PHONE12CHECK_USE'])) {
    unset($_SESSION['PHONE12CHECK_USE']); // 12check
$_SESSION['conditions'] = 1; // 12check
}
$_SESSION['hpUniqueID'] = $_GET['uniqueId'];
$next = 'checkout_confirmation.php?hp_go=1';
if (empty($_GET['payment_error'])) {
    $next = 'checkout_success.php?hp_go=1';
    $order_id = $_GET['order_id'];
    $order_id = substr($order_id, (strpos($order_id, 'Order ')+6));
    $filename = 'order_'.$order_id.'_customer_'.$_SESSION['customer_id'].'.log';
    $hp->saveSteps($filename);
    $_SESSION['cart']->reset(true);
// unregister session variables used during checkout
unset($_SESSION['sendto']);
    unset($_SESSION['billto']);
    unset($_SESSION['shipping']);
    unset($_SESSION['payment']);
    unset($_SESSION['comments']);
    unset($_SESSION['last_order']);
    unset($_SESSION['tmp_oID']);
    unset($_SESSION['cc']);
    if (isset($_SESSION['credit_covers'])) {
        unset($_SESSION['credit_covers']);
    }
}

if ($_GET['pcode'] == 'PP.PA') {
    $repl = array(
  '{AMOUNT}'        => $_GET['PRESENTATION_AMOUNT'],
  '{CURRENCY}'      => $_GET['PRESENTATION_CURRENCY'],
  '{ACC_COUNTRY}'   => $_GET['CONNECTOR_ACCOUNT_COUNTRY'],
  '{ACC_OWNER}'     => $_GET['CONNECTOR_ACCOUNT_HOLDER'],
  '{ACC_NUMBER}'    => $_GET['CONNECTOR_ACCOUNT_NUMBER'],
  '{ACC_BANKCODE}'  => $_GET['CONNECTOR_ACCOUNT_BANK'],
  '{ACC_IBAN}'      => $_GET['CONNECTOR_ACCOUNT_IBAN'],
  '{ACC_BIC}'       => $_GET['CONNECTOR_ACCOUNT_BIC'],
  '{SHORTID}'       => $_GET['IDENTIFICATION_SHORTID'],
);
    $_SESSION['hpPrepaidData'] = $repl;
    $next = 'heidelpay_success.php?hp_go=1';
} else {
    $_SESSION['hpPrepaidData'] = false;
}

if (!empty($_GET['payment_error'])) {
    $next = 'checkout_payment.php?payment_error='.$_GET['payment_error'].'&error='.urlencode($_GET['error']);
}
if ($debug) {
    echo $next;
}
if ($debug) {
    exit();
}
?><html><head><title>Heidelpay Redirect</title></head>
<body style="font-family: arial;" onLoad="document.forms[0].submit();"><center>
<br><br><br><br><br>
<h2>Ihre Daten werden &uuml;bertragen...</h2><br>
<img src="<?php echo $base?>images/ladebalken.gif">
<form action="<?php echo $base.$next.'&'.session_name().'='.session_id();?>" method="post" style="display: none;" target="_top">
<?php foreach ($_POST as $k => $v) {
    ?>
  <?php if (is_array($v)) {
        ?>
    <?php foreach ($v as $kk => $vv) {
            ?>
      <input type="text" name="<?php echo $k?>[<?php echo $kk?>]" value="<?php echo $vv?>">
    <?php 
        } ?>
  <?php 
    } else {
        ?>
    <input type="text" name="<?php echo $k?>" value="<?php echo $v?>">
  <?php 
    } ?>
<?php 
}?>
</form>
</center>
</body>
</html>
