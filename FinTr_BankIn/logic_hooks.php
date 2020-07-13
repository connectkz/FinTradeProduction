<?php
    $hook_version = 1;
    $hook_array = Array();
  
// ************ ДЕНЬГИ В БАНК *****************

$hook_array['before_save'] = Array(); 
$hook_array['before_save'][] = Array(
        //Processing index. For sorting the array.  
        1,
        //Label. A string value to identify the hook.
        'Деньги в Банк',
        //The PHP file where your class is located.
        'custom/modules/FinTr_BankIn/BankIn.php',
        //The class the method is in.
        'BankIn',
        //The method to call.
        'Add_BankIn'
);

$hook_array['before_relationship_add'] = Array();
$hook_array['before_relationship_add'][] = Array(
	2,
	'Создание дохода в модуле Поступление в банк',
	'custom/modules/FinTr_BankIn/BankIn_MoneyIn.php',
	'BankIn_MoneyIn',
	'BankIn_MoneyIn_relationship'
);

?>
