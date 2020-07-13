<?php
    $hook_version = 1;
    $hook_array = Array();
  
// ************ ВЫДАЧА СО СКЛАДА *****************

$hook_array['before_save'] = Array(); 
$hook_array['before_save'][] = Array(
        //Processing index. For sorting the array.  
        1,
        //Label. A string value to identify the hook.
        'Название модели сделать именем записи о поступлении',
        //The PHP file where your class is located.
        'custom/modules/FinTr_FromWarehouse/FromWarehouse.php',
        //The class the method is in.
        'FromWarehouse',
        //The method to call.
        'Add_FromWarehouse'
);

?>
