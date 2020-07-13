<?php
    $hook_version = 1;
    $hook_array = Array();
  
// ************ ДЕНЬГИ ИЗ КАССЫ *****************

$hook_array['before_save'] = Array(); 
$hook_array['before_save'][] = Array(
        //Processing index. For sorting the array.  
        1,
        //Label. A string value to identify the hook.
        'Деньги из кассы под отчёт',
        //The PHP file where your class is located.
        'custom/modules/FinTr_CashOut/CashOut.php',
        //The class the method is in.
        'CashOut',
        //The method to call.
        'Add_CashOut'
);

$hook_array['before_relationship_add'] = Array();
$hook_array['before_relationship_add'][] = Array(
	2,
	'CashOut',
	'custom/modules/FinTr_CashOut/CashOut_MoneyOut.php',
	'CashOut_MoneyOut',
	'CashOut_MoneyOut_relationship'
);

?>
