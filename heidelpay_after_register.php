<?php
require ('includes/application_top.php');

$payCodeBig = substr($_GET['code'], 0, 2);
$payCode = strtolower($payCodeBig);

require_once(DIR_WS_LANGUAGES.$_SESSION['language'].'/modules/payment/hp'.$payCode.'.php');
?>
<html>
<head>
<script>
  top.document.getElementById('hp<?php echo $payCode?>UniqueId').value = "<?php echo $_GET['uniqueId']?>";
  var radios = top.document.getElementsByName('payment');
  for (e in radios){
    if(radios[e].value == "hp<?php echo $payCode?>"){
      radios[e].checked = true;
    }
  }
</script>
</head>
<body>
<?php echo constant('MODULE_PAYMENT_HP'.$payCodeBig.'_DATA_SAVED')?>
</body>
</html>
