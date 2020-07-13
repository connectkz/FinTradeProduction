<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('modules/FinTr_model/FinTr_model.php');
require_once('modules/FinTr_StorageUnit/FinTr_StorageUnit.php');
require_once('modules/Relationships/Relationship.php');
require_once('data/SugarBean.php');

class FromWarehouse extends SugarBean
	{
function Add_FromWarehouse ( &$focus, $event, $arguments)
		{
/************* ВЫДАЧА СО СКЛАДА *****************
Если никакая складская единица не выбрана - отменить выдачу
Если количество выдаваемого введено больше количества у складской единицы - отменить выдачу
Серийный номер передать выдаче со склада от складской единицы
Если количество выдаваемого равно количеству у складской единицы - пометить складскую единицу как удалённую
Если количество выдаваемого меньше количества у складской единицы - уменьшить количество у складской единицы
Связать Выдачу со склада с моделью, складом, брендом и типом оборудования
Если количество всех складских единиц по модели равно 0, пометить Складскую позицию как удалённую

Выдача под отчёт. Одна выдача со склада - одна запись Материалов под отчёт


*/
$one = 1;
$zero = 0;

$fromwarehouse_name = $focus->fintr_storageunit_fintr_fromwarehouse_name; 	
$focus->name = $fromwarehouse_name; 			//берём название Складской единицы и подставляем
$issued = $focus->valueout; 			//количество выдаваемого со склада
//$GLOBALS['log']->fatal("Выдаём в количестве .$issued");
$issued_serial_number = $focus->serial_number; 	//серийный номер выдаваемого со склада
$thisid = $focus->id; 				//на всякий случай id Выдачи со склада
$assigned_user_id = $focus->assigned_user_id;	//Кому выдваём под отчёт
$assigned_user_name = $focus->assigned_user_name;	//Кому выдваём под отчёт
$description = $focus->description; 		//

//Если никакая складская единица не выбрана - отменить выдачу
if  ($fromwarehouse_name == ''){
$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_FromWarehouse',
		'action' => 'EditView',
		'record' => $whatever,
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
}
else {
}
//Если количество выдаваемого поставлено 0 - отменить выдачу
if  ($issued == '0'){
$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_FromWarehouse',
		'action' => 'EditView',
		'record' => $whatever,
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
}
else {
}
//Если количество выдаваемого введено больше количества у складской единицы - отменить выдачу
//+ Получить количество у Складской единицы
//++Получить id Складской единицы

$focus->retrieve($thisid);//А ЗАЧЕМ??? $focus УЖЕ retrieve`d ($thisid); ?
$focus->load_relationship('fintr_storageunit_fintr_fromwarehouse');
$list = array();
foreach ($focus->fintr_storageunit_fintr_fromwarehouse->getBeans(new FinTr_StorageUnit()) as $storageunits) {
    $list[$storageunits->id] = $storageunits;
}
/*
$storageunit_all = print_r($list, true);
$GLOBALS['log']->fatal("Складская Полностью .$storageunit_all");
*/
$storageunit_id = $storageunits->id;// Получили id Складской единицы
$storageunit_value = $storageunits->value_in_storageunit;// Получили количество Складской единицы
$storageunit_serial_number = $storageunits->serial_number; //Получили серийный номер Складской единицы
$storageunit_brand = $storageunits->fintr_brand_fintr_storageunitfintr_brand_ida; ////Получили id Бренда
$storageunit_type = $storageunits->fintr_type_fintr_storageunitfintr_type_ida;//Получили id Типа оборудования 
$storageunit_warehouse = $storageunits->fintr_warehouse_fintr_storageunitfintr_warehouse_ida; //Получили id Складской позиции
$storageunit_model_id = $storageunits->fintr_model_fintr_storageunitfintr_model_ida; //Получили id Модели
//$GLOBALS['log']->fatal("Складская единица имеет модель . $storageunit_model");
//$GLOBALS['log']->fatal("Складская единица имеет бренд . $storageunit_brand");

//Если количество выдаваемого введено больше количества у складской единицы - отменить выдачу 
if  ($issued > $storageunit_value){

$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_FromWarehouse',
		'action' => 'EditView',
		'record' => $whatever,
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
}
else {
}
//$GLOBALS['log']->fatal("Выдаём в количестве . $issued");

//Серийный номер передать выдаче со склада от складской единицы
$focus->serial_number = $storageunit_serial_number;

//Если количество выдаваемого равно количеству у складской единицы - указать количетсво у складской единицы 0 и пометить складскую единицу как удалённую
if  ($issued == $storageunit_value){
$storageunit = new FinTr_StorageUnit();
$storageunit->retrieve($storageunit_id);
$storageunit->value_in_storageunit = $zero;
$storageunit->deleted = $one;
}
else{
}
//Если количество выдаваемого меньше количества у складской единицы - уменьшить количество у складской единицы
$rest = $storageunit_value - $issued;
if ($rest > $zero){
//$GLOBALS['log']->fatal("Остаётся . $rest");
$storageunit = new FinTr_StorageUnit();
$storageunit->retrieve($storageunit_id);
$storageunit->value_in_storageunit = $storageunit->value_in_storageunit - $issued;
}
else{
}
$storageunit->save();

//Связываем Выдачу со склада с моделью, складом, брендом и типом оборудования
//   Связываем Выдачу со склада с моделью   
$rel_name = 'fintr_model_fintr_fromwarehouse';// Мы знаем имя связи между Моделью и Выдачей со склада
//Теперь устанавливаем связь между 'FinTr_model' и 'FinTr_FromWarehouse', т.е. между Моделью и Выдачей со склада
$rel_model = new FinTr_model();
$rel_model->retrieve($storageunit_model_id);
$GLOBALS['log']->fatal("id модели . $storageunit_model_id");
$rel_model->load_relationship($rel_name);
$rel_model->$rel_name->add($thisid);
$rel_model->save();

//   Связываем Выдачу со склада со складом
$rel_name = 'fintr_warehouse_fintr_fromwarehouse';// Мы знаем имя связи между Складом и Выдачей со склада
//Теперь устанавливаем связь между 'FinTr_warehouse' и 'FinTr_FromWarehouse', т.е. между Складом и Выдачей со склада
$rel_warehouse = new FinTr_warehouse();
$rel_warehouse->retrieve($storageunit_warehouse);
$rel_warehouse->load_relationship($rel_name);
$rel_warehouse->$rel_name->add($thisid);
$rel_warehouse->save();

//   Связываем Выдачу со склада с брендом 
//Если Бренд вообще есть у данной складской единицы
if ($storageunit_brand != '') {
$rel_name = 'fintr_brand_fintr_fromwarehouse';// Мы знаем имя связи между Брендом и Выдачей со склада
//Теперь устанавливаем связь между 'FinTr_brand' и 'FinTr_FromWarehouse', т.е. между Брендом и Выдачей со склада
$rel_brand = new FinTr_brand();
$rel_brand->retrieve($storageunit_brand);
$rel_brand->load_relationship($rel_name);
$rel_brand->$rel_name->add($thisid);
$rel_brand->save();
}
else {
}

//Связываем Выдачу со склада типом оборудования
$rel_name = 'fintr_type_fintr_fromwarehouse';// Мы знаем имя связи между Типом оборудования и Выдачей со склада
//Теперь устанавливаем связь между 'FinTr_Type' и 'FinTr_FromWarehouse', т.е. между Типом оборудования и Выдачей со склада
$rel_type = new FinTr_Type();
$rel_type->retrieve($storageunit_type);
$rel_type->load_relationship($rel_name);
$rel_type->$rel_name->add($thisid);
$rel_type->save();

//Если количество всех складских единиц  равно 0, пометить Складскую позицию как удалённую
//+Получить список всех складских единиц НЕУДАЛЁННЫХ связанных с данной моделью 
$rel_name = 'fintr_warehouse_fintr_storageunit';// Мы знаем имя связи между Складом и Складской единицей
$rel_warehouse = new FinTr_warehouse();
$rel_warehouse->retrieve($storageunit_warehouse);
$rel_warehouse->load_relationship($rel_name);
$storageunitlist = array();//Соберём сюда все связанные Складские единицы
$storageunit_sum = 0;
foreach ($rel_warehouse->$rel_name->getBeans(new FinTr_StorageUnit()) as $storageunit) {
    $storageunitlist[$storageunit->id] = $storageunit;
$storageunit_sum = $storageunit_sum + $storageunit->value_in_storageunit; //Заодно суммируем все количества по складским единицам
}
$rel_warehouse->sum = $storageunit_sum;//Сумма на склад ложится полюбому
if ($storageunit_sum == $zero) { //Если сумма по всем складским единицам - 0
$rel_warehouse->deleted = $one; // Помечаем Складскую позицию как удалённую
}
$rel_warehouse->save(); //Сохраняем
//$storageunit_count = count($storageunitlist);
//$GLOBALS['log']->fatal("Складских единиц . $storageunit_count");
//$GLOBALS['log']->fatal("Склад Полностью .$storageunit_sum");

//Выдача под отчёт. Одна выдача со склада - одна запись Материалов под отчёт
// 
$rel_model = 'fintr_model_fintr_goodscredit';// Мы знаем имя связи между Моделью и Материалами под отчёт
$GoodsCredit = new FinTr_GoodsCredit();
$GoodsCredit->save(); //Сохраняем новую запись Материалов под отчёт
$GoodsCredit->load_relationship($rel_model);
$GoodsCredit->$rel_model->add($storageunit_model_id);

$GoodsCredit->name = $assigned_user_name .' получил ' . $fromwarehouse_name;
$GoodsCredit->value = $issued;
$GoodsCredit->assigned_user_id = $assigned_user_id;
$GoodsCredit->description = $description;
$GoodsCredit->save(); //Сохраняем запись Материалов под отчёт со всеми изменениями

$rel_model = 'fintr_fromwarehouse_fintr_goodscredit';// Мы знаем имя связи между Выдачей со склада и Материалами под отчёт
$GoodsCredit->load_relationship($thisid);
$GoodsCredit->$rel_model->add($thisid);
$GoodsCredit->save(); //Сохраняем запись Материалов под отчёт вместе со связью с Выдачей со склада

/*

$one = 1;
$zero = 0;

*/
	}

	}

?>

