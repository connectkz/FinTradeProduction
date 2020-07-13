<?php
    $hook_version = 1;
    $hook_array = Array();
  

$hook_array['before_save'] = Array(); 
$hook_array['before_save'][] = Array(
        //Processing index. For sorting the array.  
        1,
        //Label. A string value to identify the hook.
        'Название модели сделать именем записи о поступлении',
        //The PHP file where your class is located.
        'custom/modules/FinTr_ToWarehouse/ToWarehouse.php',
        //The class the method is in.
        'ToWarehouse',
        //The method to call.
        'Add_ToWarehouse'
);
/*
$hook_array['after_save'] = Array(); 
$hook_array['after_save'][] = Array(
        //Processing index. For sorting the array.  
        2,
        //Label. A string value to identify the hook.
        'Получить из Модели значение поля серийный номер, т.е. узнать, обязателен ли серийный номер',
        //The PHP file where your class is located.
        'custom/modules/FinTr_ToWarehouse/Serial_number.php',
        //The class the method is in.
        'Serial_number',
        //The method to call.
        'Get_Serial_number'
    );
 */
?>

