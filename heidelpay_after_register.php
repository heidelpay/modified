<?php
/**
 * iframe message after registration
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
require('includes/application_top.php');

$payCodeBig = substr($_GET['code'], 0, 2);
$payCode = strtolower($payCodeBig);

$currentLanguage = ($_SESSION['language'] === 'german') ? 'german' : 'english';

$usedPaymentMethod = ($payCode === 'dc') ? 'dc' : 'cc';

require_once(DIR_WS_LANGUAGES.$currentLanguage.'/modules/payment/hp'.$usedPaymentMethod.'.php');
?>
<html>
<head>
<script>
    <?php $uniqueId = htmlspecialchars($_GET['uniqueId']);
    echo 'top.document.getElementById(\'hp'.$usedPaymentMethod.'UniqueId\').value = "'.$uniqueId.'";';
    ?>
  var radios = top.document.getElementsByName('payment');
  for (e in radios){
    if(radios[e].value == "hp<?php echo $usedPaymentMethod?>"){
      radios[e].checked = true;
    }
  }
</script>
</head>
<body>
<?php echo constant('MODULE_PAYMENT_HP'.strtoupper($usedPaymentMethod).'_DATA_SAVED')?>
</body>
</html>