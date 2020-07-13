<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('modules/FinTr_model/FinTr_model.php');
require_once('modules/FinTr_StorageUnit/FinTr_StorageUnit.php');
require_once('modules/Relationships/Relationship.php');
require_once('data/SugarBean.php');

class ToWarehouse extends SugarBean
	{
function Add_ToWarehouse ( &$focus, $event, $arguments)
		{
$one = 1;
$zero = 0;
/************* ПОСТУПЛЕНИЕ НА СКЛАД *****************


*/
$status = $focus->status; 			//Статус - т.е. создаваемое или редактируемое. Редактировать нельзя. Статус  closed
$place = $focus->place;				// Место на складе
$added = $focus->valuein; 			//количество поступающего на склад
$added_serial_number = $focus->serial_number; 	//серийный номер поступающего на склад
$thisid = $focus->id; 				//на всякий случай id Поступления на склад 
$add_to_storage_unit = $focus->add_to_storageunit; //указывает, добавить к существующей складской единице, или создать новую. Вместе с тем, есть у модели поле merged
//Если Статус - closed - Отменить ввод и перейти к списку поступлений на склад
if ($status == 'closed')
		{
		$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_ToWarehouse',
		'action' => 'ListView',
		'record' => $whatever,
		);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
		}
//Если указали количество 0 - Отменить ввод
if ($added == 0)
		{
		$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_ToWarehouse',
		'action' => 'EditView',
		'record' => $whatever,
		);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
		}
//Если не указали Модель - Отменить ввод
if ($focus->fintr_model_fintr_towarehouse_name == '')
		{
		$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_ToWarehouse',
		'action' => 'EditView',
		'record' => $whatever,
		);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
		}

$focus->name = $focus->fintr_model_fintr_towarehouse_name; 			//берём название модели и подставляем
$model = new FinTr_model();
//            ЗАСАДА?????????????????????????????
$related_model = $focus->fintr_model_fintr_towarehousefintr_model_ida;//какую модель выбрали, у той и ищем. По id
$model->retrieve($related_model); 
$require_serial = $model->serialnumber;//Требуется серийный номер или нет. Что-то потом придумать, что бы не позволяло сохранить с пустым полем серийного номера.

$model->save();//только для контроля. СТРОКУ УДАЛИТЬ

$model_id = $model->id;
// Нужно получить id  бренда от модели. Потом использовать его для связи со Складской позицией  и Поступлением на склад и Складом
$model->retrieve($model_id);
$rel_name = 'fintr_brand_fintr_model';// Мы знаем имя связи между брендом и моделью
$model->load_relationship($rel_name);
$brandlist = array();
foreach ($model->$rel_name->getBeans(new FinTr_brand()) as $brands) {
    $brandlist[$brands->id] = $brands;
}
if (count($brandlist) == $one){
$brand_id = $brands->id; // Получили id бренда
$brand_name = $brands->name;//и имя бренда до кучи, хотя и не используем. Пока
}
// Нужно получить id типа оборудования от модели. Потом использовать его для связи со Складской позицией  и Поступлением на склад и Складом
$model->retrieve($model_id);
//reset($model);
$rel_name = 'fintr_type_fintr_model';// Мы знаем имя связи между типом и моделью
$model->load_relationship($rel_name);
$typezlist = array();
foreach ($model->$rel_name->getBeans(new FinTr_Type()) as $sometypez) {
    $typezlist[$sometypez->id] = $sometypez;
}
if (count($typezlist) == $one){
$type_id = $sometypez->id; // Получили id типа
$type_name = $sometypez->name;//и имя типа до кучи, хотя и не используем. Пока
}

//Если Складская единица с таким серийным номером уже поступала ранее и была выдана, то не создавать новую Складскую единицу
//
//=========================================================================================================================
$storageunit = new FinTr_StorageUnit();
$storageunit_deleted_id = 0;
if ($added_serial_number != '')
	{
$storageunit->retrieve_by_string_fields(array('serial_number' => $added_serial_number, 'deleted' => 1), $encode=true, $deleted=false); //Ищем Складскую единицу с НЕПУСТЫМ серийным номером среди УДАЛЁННЫХ Складских единиц
	if ($storageunit->serial_number == $added_serial_number)//И если такой серийный номер найден
//$storageunit_deleted_id = $storageunit->serial_number;
		{
		if ($added == 1) //И количество = 1  - Восстановить Складскую единицу
			{
$storageunit->mark_undeleted($storageunit->id);
			$storageunit->deleted = $zero; //Пометить Складскую единицу как не удалённую
			$storageunit->new_with_id = false; //Требуется или нет????
			$add_to_storage_unit = 0; //Но точно не добавлять к существующей записи на основании имени модели
			}
		else //А если количество не равно 1, т.е. 0 или больше 1 - Отменить ввод
			{
			$queryParams = array(
	             	   	'action' => 'ajaxui#ajacUILoc=index.php',
				'module' => 'FinTr_ToWarehouse',
				'action' => 'EditView',
				'record' => $whatever,
				);
			SugarApplication::redirect('index.php?' . http_build_query($queryParams));
			}
		}

	else 	//А раз среди УДАЛЁННЫХ Складских единиц её нет
		{
		$storageunit->retrieve_by_string_fields(array('serial_number' => $added_serial_number), $encode=true, $deleted=true); //Ищем Складскую единицу с НЕПУСТЫМ серийным номером среди НЕУДАЛЁННЫХ Складских единиц
//$storageunit_is_deleted = (bool) $storageunit->deleted; 
		if ($storageunit->serial_number == $added_serial_number)//И если такой серийный номер найден - ОТМЕНИТЬ ПОЛЮБОМУ
			{
//			$GLOBALS['log']->fatal("storageunit_is_deleted . $storageunit_is_deleted added_serial_number $added_serial_number require_serial $require_serial");
			$queryParams = array(
	             	   	'action' => 'ajaxui#ajacUILoc=index.php',
				'module' => 'FinTr_ToWarehouse',
				'action' => 'EditView',
				'record' => $whatever,
				);
			SugarApplication::redirect('index.php?' . http_build_query($queryParams));
			}
		else if ($added == 1)//А Если такой серийный номер не найден и Серийный номер НЕПУСТОЙ(это ещё раньше проверили) и добавляемое количество 1
			{
			$storageunit = new FinTr_StorageUnit();
			$storageunit->name = $focus->fintr_model_fintr_towarehouse_name;//Создаём новую Складскую единицу
//			$storageunit->add_to_storage_unit = '0'; //Даже если указано добавить к существующей Складской единице
//$GLOBALS['log']->fatal("add_to_storage_unit . $add_to_storage_unit added_serial_number $added_serial_number");
		
			}
		else if ($added > 1)//Если Серийный номер НЕПУСТОЙ и добавляемое количество больше 1 - Отменить ввод из-за неопределённости независимо от того  требуется серийный номер или нет. 
			{
			$queryParams = array(
                		'action' => 'ajaxui#ajacUILoc=index.php',
				'module' => 'FinTr_ToWarehouse',
				'action' => 'EditView',
				'record' => $whatever,
				);
			SugarApplication::redirect('index.php?' . http_build_query($queryParams));
			}
		}
	}
else if($added_serial_number == '') //Если серийный номер ПУСТОЙ
	{
	if ($require_serial == 'yes') //А для этой модели серийный номер обязателен - Отменить ввод
		{
		$queryParams = array(
                	'action' => 'ajaxui#ajacUILoc=index.php',
			'module' => 'FinTr_ToWarehouse',
			'action' => 'EditView',
			'record' => $whatever,
				);
		SugarApplication::redirect('index.php?' . http_build_query($queryParams));
		}
	else if ($add_to_storage_unit == 0) //Серийный номер НЕ обязателен И не добавляем к существующй Складской единице, то создаём новую
		{
		$storageunit = new FinTr_StorageUnit();
		$storageunit->name = $focus->fintr_model_fintr_towarehouse_name;//Создаём новую Складскую единицу
		}
	else if ($add_to_storage_unit == 1) 
		{
		$storageunit = new FinTr_StorageUnit();
		$storageunit->name = $focus->fintr_model_fintr_towarehouse_name;//Цепляем к первой попавшейся, TODO НО ХОТЕЛОСЬ БЫ ЭТОГО ЖЕ БРЕНДА. Если Складской единицы с таким наименованием и с таким брендом нет - то создать новую складскую единицу
		$storageunit->retrieve_by_string_fields(array('name' => $focus->fintr_model_fintr_towarehouse_name));//Здесь можно добавить БРЕНД
		}
	}
$storageunit->value_in_storageunit = $storageunit->value_in_storageunit + $added;//в любом случае увеличиваем количество у Складской единицы
$storageunit->serial_number = $added_serial_number;
$storageunit->place = $place;// Место на складе
$storageunit->save();

$rel_name = 'fintr_model_fintr_storageunit';// Мы знаем имя связи между Моделью и Складской единицей
//Теперь устанавливаем связь между 'FinTr_StorageUnit' и 'FinTr_model', т.е. между Моделью и Складской единицей
$storageunitid = $storageunit->id;
$storageunit->retrieve($storageunitid);
$storageunit->load_relationship($rel_name);
$storageunit->$rel_name->add($model_id);
$storageunit->save(); //1340

//Следующие четыре строки для получения имени связи
global $db,$dictionary,$beanList;
$rel = new Relationship();
$rel_info = $rel->retrieve_by_sides('FinTr_StorageUnit', 'FinTr_ToWarehouse', $db);
$rel_name = $rel_info['relationship_name'];
//Теперь устанавливаем связь между 'FinTr_StorageUnit' и 'FinTr_ToWarehouse', т.е. между Поступлением на склад и Складской единицей
$storageunitid = $storageunit->id;
$storageunit->retrieve($storageunitid);
$storageunit->load_relationship($rel_name);
$storageunit->$rel_name->add($thisid);
$storageunit->save();

//FinTr_warehouse /////////////////////__СКЛАД__////////////////////////////
$warehouse = new FinTr_warehouse();
$warehouse->retrieve_by_string_fields(array('name' => $focus->fintr_model_fintr_towarehouse_name));
$warehouse->name = $focus->fintr_model_fintr_towarehouse_name;
$warehouse->sum = $warehouse->sum  + $added; //увеличиваем количество на складе
$warehouse->save();

//Следующие четыре строки для получения имени связи
global $db,$dictionary,$beanList;
$relwarehouse = new Relationship();
$rel_info = $rel->retrieve_by_sides('FinTr_warehouse', 'FinTr_ToWarehouse', $db);
$rel_name = $rel_info['relationship_name'];
//Теперь устанавливаем связь между 'FinTr_warehouse' и 'FinTr_ToWarehouse', т.е. между Складской позицией  и Поступлением на склад
$warehouse_id = $warehouse->id;
$warehouse->retrieve($warehouse_id);
$warehouse->load_relationship($rel_name);
$warehouse->$rel_name->add($thisid);
$warehouse->save();

//Следующие четыре строки для получения имени связи
global $db,$dictionary,$beanList;
$rel_warehouse_storageunit = new Relationship();
$rel_info = $rel->retrieve_by_sides('FinTr_warehouse', 'FinTr_StorageUnit', $db);
$rel_name = $rel_info['relationship_name'];
//Теперь устанавливаем связь между 'FinTr_warehouse' и 'FinTr_StorageUnit', т.е. между Складской позицией и Складской единицей
$warehouse_id = $warehouse->id;
$warehouse->retrieve($warehouse_id);
$warehouse->load_relationship($rel_name);
$warehouse->$rel_name->add($storageunitid);
$warehouse->save();

$rel_name = 'fintr_model_fintr_warehouse';// Мы знаем имя связи между Моделью и Складской позицией
//Теперь устанавливаем связь между 'FinTr_warehouse' и 'FinTr_model', т.е. между Складской позицией и Моделью
//$warehouse_id = $warehouse->id;
$warehouse->retrieve($warehouse_id);
$warehouse->load_relationship($rel_name);
$warehouse->$rel_name->add($model_id);
$warehouse->save();

$how_much_brands = count($brandlist);//Если бренд есть, то добавляем связь бренда с Поступлением на склад, Складской единицей и Складом
if ($how_much_brands != 0){
$brand = new FinTr_brand();
$brand->retrieve($brand_id);
$rel_name = 'fintr_brand_fintr_towarehouse';// Мы знаем имя связи между Брендом и Поступлением на склад 
$brand->load_relationship($rel_name);
$brand->$rel_name->add($thisid);
$brand->save();
$rel_name = 'fintr_brand_fintr_warehouse';// Мы знаем имя связи между Брендом и Складом
$brand->load_relationship($rel_name);
$brand->$rel_name->add($warehouse_id);
$brand->save();
$rel_name = 'fintr_brand_fintr_storageunit';// Мы знаем имя связи между Брендом и Складской единицей
$brand->load_relationship($rel_name);
$brand->$rel_name->add($storageunitid);
$brand->save();
}
else {
}

$how_much_types = count($typezlist);//Если Тип оборудования есть, то добавляем связь Типа оборудования с Поступлением на склад, Складской единицей и Складом
if ($how_much_types != 0){
$goods_type = new FinTr_Type();
$goods_type->retrieve($type_id);
$rel_name = 'fintr_type_fintr_towarehouse';// Мы знаем имя связи между Типом оборудования и Поступлением на склад 
$goods_type->load_relationship($rel_name);
$goods_type->$rel_name->add($thisid);
$goods_type->save();
$rel_name = 'fintr_type_fintr_warehouse';// Мы знаем имя связи между Типом оборудования и Складом
$goods_type->load_relationship($rel_name);
$goods_type->$rel_name->add($warehouse_id);
$goods_type->save();
$rel_name = 'fintr_type_fintr_storageunit';// Мы знаем имя связи между Типом оборудования и Складской единицей
$goods_type->load_relationship($rel_name);
$goods_type->$rel_name->add($storageunitid);
$goods_type->save();
}
else {
}	
$focus->status = 'closed'; //Всё, запись закрыта от редактирования. От любого
	}

	}

?>

