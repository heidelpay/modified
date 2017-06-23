<?php
require('includes/application_top.php');

if (!empty($_SESSION['hpLastPost'])) {
    $_POST = $_SESSION['hpLastPost'];
    $next = 'checkout_success.php';
    include_once(DIR_WS_CLASSES.'class.heidelpay.php');
    $filename = 'order_'.$_SESSION['tmp_oID'].'_customer_'.$_SESSION['customer_id'].'.log';
    $hp = new heidelpay();
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

    $base = HTTPS_SERVER.DIR_WS_CATALOG; ?>

<html><head><title>Heidelpay Redirect</title></head>
<body onLoad="document.forms[0].submit()"><center>
<br><br><br><br><br><br><br><br><br><br><br>
<h2>Ihre Daten werden gepr&uuml;ft...</h2><br>
<img src="<?php echo $base?>images/ladebalken.gif">
<form action="<?php echo $base.$next.'?'.session_name().'='.session_id()?>&3ds=1"
      method="post" style="display: none" target="_top">
<?php foreach ($_POST as $k => $v) {
        ?>
  <?php if (is_array($v)) {
            ?>
    <input type="text" name="<?php echo $k?>[<?php echo key($v)?>]" value="<?php echo current($v)?>">
  <?php 
        } else {
            ?>
    <input type="text" name="<?php echo $k?>" value="<?php echo $v?>">
  <?php 
        } ?>
<?php 
    } ?>
</form>
</center>
</body>
</html>
<?php 
} else {
    echo 'Lost Session or Post Data...';
}?>
