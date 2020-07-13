<?php
    $hook_version = 1;
    $hook_array = Array();
  
// ************ ДЕНЬГИ В КАССУ *****************

$hook_array['before_save'] = Array(); 
$hook_array['before_save'][] = Array(
        //Processing index. For sorting the array.  
        1,
        //Label. A string value to identify the hook.
        'Деньги в кассу',
        //The PHP file where your class is located.
        'custom/modules/FinTr_CashIn/CashIn.php',
        //The class the method is in.
        'CashIn',
        //The method to call.
        'Add_CashIn'
);

$hook_array['before_relationship_add'] = Array();
$hook_array['before_relationship_add'][] = Array(
	1,
	'Создание дохода в модуле Поступление в кассу',
	'custom/modules/FinTr_CashIn/CashIn_MoneyIn.php',
	'CashIn_MoneyIn',
	'CashIn_MoneyIn_relationship'
);

?>
