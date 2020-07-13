<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('data/SugarBean.php');
require_once('modules/Users/User.php');
require_once('modules/Relationships/Relationship.php');
require_once('modules/FinTr_cashbox/FinTr_cashbox.php');

class CashOut extends SugarBean
	{
function Add_CashOut ( &$focus, $event, $arguments)
		{
/************* ВЫДАЧА ИЗ КАССЫ *****************
Если сумма меньше 1 - отменить ввод
Если статус == closed, не вычитать из Кассы
 Найти Кассу (т.к. касс может быть больше одной) ответственный за которую = $current_user
  Если такой кассы не нашлось - отменить ввод
  Если сумма к выдаче больше суммы в кассе - отменить ввод
 Связать Кассу и Выдачу из кассы
 Уменьшить сумму в найденной кассе на сумму выдачи из кассы.
 Увеличить Сумму денег выданной под отчёт из этой кассы
 Прибавить сумму выдачи из кассы к сумме подотчёта получившего из кассы.
  Найти подотчёт назначенного пользователя
  Связать Подотчёт и Выдачу из кассы
*********  
TODO: Админская настройка - может ли один пользователь быть ответственным за разные кассы. 
* Тогда нужно добавить поле для выбора кассы. 

TODO: Если касса не одна, то желателен подотчёт по каждой кассе,  который связан с Подотчётом (один ко многим)
* тогда и наличные в банк проще с подотчёта снимать
*/
global $db,$dictionary,$beanList;
global $current_user;

$one = 1;
$zero = 0;

$CashOut_value = $focus->currency;
$CashOut_sum_const = $focus->sum_const;//Защита от редактирования суммы выдачи из кассы
$CashOut_id = $focus->id;
$CashOut_user = $focus->modified_user_id;//кто создаёт запись
$CashOut_status = $focus->status;
$CashCredit_user = $focus->assigned_user_id;//кто получает деньги - тот назначается ответсвенным
$CashCredit_user_name = $focus->assigned_user_name;//кто получает деньги - тот назначается ответсвенным


// Если сумма меньше 1 - отменить ввод
if  (($CashOut_value == '') OR ($CashOut_value < '1'))
			{
$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_CashOut',
		'action' => 'EditView',
		'record' => $whatever,
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
			}
else 			{
			}


//Если статус == closed, не вычитать из Кассы
if ($CashOut_status == 'closed')
			{
$focus->currency = $CashOut_sum_const;//и не менять сумму выдачи из кассы
//return;
			}
else
			{
//Найти id Касы (т.к. касс может быть больше одной) ответственный за которую = $CashOut_user
$my_cashbox = new FinTr_cashbox();
$order_by = "";
$where_user = "assigned_user_id = '$CashOut_user'";
$check_dates = false;
$show_deleted = 0;
$list_my_cashbox = $my_cashbox->get_full_list($order_by, $where_user, $check_dates, $show_deleted);
$count_my_cashbox = count($list_my_cashbox);
if ($count_my_cashbox == $one)	{
$my_cashbox = $list_my_cashbox[0];
$my_cashbox_id = $my_cashbox->id;//нашли id Касы
				}
else				//НО если НЕ нашли, или Касс больше 1
				{

$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_CashOut',
		'action' => 'ListView',
		'record' => $whatever,
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));//Возвращаемся к списку Выдачи из кассы
				}
if ($CashOut_value > $my_cashbox->currency)//Если сумма к выдаче больше суммы в кассе - отменить ввод
					{
$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_CashOut',
		'action' => 'EditView',
		'record' => $whatever,
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
					}


//Уменьшить сумму в найденной кассе на сумму выдачи из кассы.
$rel_name = 'fintr_cashbox_fintr_cashout';//Мы знаем имя связи 
$my_cashbox->load_relationship($rel_name);
if ($my_cashbox->isOwner($CashOut_user))
						{
$my_cashbox->currency = $my_cashbox->currency - $CashOut_value;//Уменьшили сумму в найденной кассе на сумму Выдачи из кассы.
$my_cashbox->cashcredit = $my_cashbox->cashcredit + $CashOut_value;//Увеличили Сумму денег под отчёт в найденной кассе на сумму Выдачи из кассы.
$my_cashbox->$rel_name->add($CashOut_id);//Связали Кассу и Выдачу из кассы
$focus->status = 'closed';//Уставновили метку. Запись 'закрыта' для редактирования суммы Выдачи из кассы
$focus->sum_const = $CashOut_value;//Сохранили 'неизменяемую' сумму Выдачи из кассы
$my_cashbox->save();
						}
else 
						{
$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_cashbox',
		'action' => 'ListView',
		'record' => $whatever,
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
						}//закрываем else -- if ($my_cashbox->isOwner($CashOut_user))

//Прибавить сумму выдачи из кассы к сумме подотчёта получившего из кассы.
///Найти подотчёт пользователя, получающего деньги из кассы
$my_CashCredit = new FinTr_CashCredit();
$my_CashCredit->name = "Отчитывается - $CashCredit_user_name";
$my_CashCredit->assigned_user_id = $CashCredit_user;
//$my_CashCredit_description = print_r($my_CashCredit, true);
//$my_CashCredit_id = $my_CashCredit->id;//Здесь id пустой
//$GLOBALS['log']->fatal("Деньги под отчёт .$my_CashCredit_description"); 
$order_by = "";
$where_user = "assigned_user_id = '$CashCredit_user'"; //
//TODO добавить к критерию поиска $my_cashbox_id, т.е. Кассу из которой выдаются деньги. На случай, еслм касс больше одной
$check_dates = false;
$show_deleted = 0;
$list_my_CashCredit = $my_CashCredit->get_full_list($order_by, $where_user, $check_dates, $show_deleted);
$count_my_CashCredit = count($list_my_CashCredit);

if ($count_my_CashCredit == $one)	{
$my_CashCredit = $list_my_CashCredit[0];
$my_CashCredit_id = $my_CashCredit->id;//нашли id ранее созданного подотчёта пользователя

					}
//$my_CashCredit->retrieve($my_CashCredit_id);
$my_CashCredit->currency = $my_CashCredit->currency + $CashOut_value;//Прибавили сумму выдачи из кассы к сумме подотчёта получившего из кассы.
$my_CashCredit->save();
//Связать Подотчёт и Выдачу из кассы
$rel_name = 'fintr_cashcredit_fintr_cashout';//Мы знаем имя связи
$my_CashCredit->load_relationship($rel_name);
$my_CashCredit->$rel_name->add($CashOut_id);//Связали Подотчёт и Выдачу из кассы
$my_CashCredit->save();


//НИЖЕ НИЧЕГО НЕ ГОТОВО

			}//закрываем else -- if ($CashOut_status == 'closed')

		}

	}

?>
