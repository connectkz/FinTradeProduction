<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class CashIn extends SugarBean
	{
function Add_CashIn ( &$focus, $event, $arguments)
		{
/************* ПОСТУПЛЕНИЕ В КАССУ *****************
Если сумма меньше 1 - отменить ввод
Если статус == closed, не добавлять к Кассе
  Найти Кассу (т.к. касс может быть больше одной) ответственный за которую = $current_user
  Если такой кассы не нашлось - отменить ввод и Возвращаемся к списку Поступлений в кассу
  Если тип внесения - возврат из подотчёта, то найти подотчёт и если он нашёлся и сумма под отчётом НЕ меньше вносимой суммы- уменьшить сумму подотчёта.
  Если тип внесения - Оплата покупателей, то............
  Увеличить сумму в найденной кассе на сумму поступления в кассу.
  Связать Кассу и Поступление в кассу
*********  Админская настройка - может ли один пользователь быть ответственным за разные кассы
*/
global $db,$dictionary,$beanList;
global $current_user;

$one = 1;
$zero = 0;
$CashIn_value = $focus->currency;
$CashIn_type = $focus->type;
$CashIn_sum_const = $focus->sum_const;
$CashIn_id = $focus->id;
$CashIn_user = $focus->modified_user_id;//id создающего запись, т.е. кассира
$CashIn_status = $focus->status;
$CashDebet_user_id = $focus->assigned_user_id;//id вносящего в кассу
//$CashDebet_summ = $focus->cashdebet;

// Если сумма меньше 1 - отменить ввод
if  (($CashIn_value == '') OR ($CashIn_value < '1'))
			{
$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_CashIn',
		'action' => 'EditView',
		'record' => $whatever,
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
			}
else 			
			{
			}
//Если статус = closed, не добавлять к Кассе
if ($CashIn_status == 'closed')
			{
$focus->currency = $CashIn_sum_const;	//и не менять сумму поступления в кассу
					//Но всё остальное может меняться, включая ТИП ВНЕСЕНИЯ!!! 
			}
else
			{
/*
Найти id Касы (т.к. касс может быть больше одной) ответственный за которую = $CashIn_user
*/
$my_cashbox = new FinTr_cashbox();
$order_by = "";
$where_user = "assigned_user_id = '$CashIn_user'";
$check_dates = false;
$show_deleted = 0;
$list_my_cashbox = $my_cashbox->get_full_list($order_by, $where_user, $check_dates, $show_deleted);
$count_my_cashbox = count($list_my_cashbox);
	if ($count_my_cashbox == $one)	
				{
$my_cashbox = $list_my_cashbox[0];
$my_cashbox_id = $my_cashbox->id;	//нашли id Касы
				}
	else				//НО если НЕ нашли
				{

$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_CashIn',
		'action' => 'ListView',
		'record' => $whatever,
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
					//Возвращаемся к списку Поступлений в кассу
				}
/*Если тип внесения - Возврат от подотчётника - найти подотчёт вносящего и проверить сумму под отчётом. Если сумма под отчётом меньше вносимой суммы - отменить ввод.
*/
if ($CashIn_type == 'cashback')
{
/*
Находим подотчёт вносящего
*/
$my_CashCredit = new FinTr_CashCredit();
$order_by = "";
$where_user = "assigned_user_id = '$CashDebet_user_id'"; //
//TODO: добавить к критерию поиска $my_cashbox_id, т.е. Кассу из которой выдаются деньги. На случай, если касс больше одной
$check_dates = false;
$show_deleted = 0;
$list_my_CashCredit = $my_CashCredit->get_full_list($order_by, $where_user, $check_dates, $show_deleted);
$count_my_CashCredit = count($list_my_CashCredit);
	if ($count_my_CashCredit == $one)//Если нашёлся 1 подотчёт вносящего
				{
$my_CashCredit = $list_my_CashCredit[0];
$my_CashCredit_id = $my_CashCredit->id;	//находим id ранее созданного подотчёта вносящего
$my_CashCredit->retrieve($my_CashCredit_id);
		if ($my_CashCredit->currency < $CashIn_value)	//Если сумма под отчётом меньше вносимой суммы 
					{
$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_CashIn',
		'action' => 'EditView',
		'record' => $whatever,
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));//- отменить ввод.
					
					}
		else						//А если сумма под отчётом больше вносимой суммы 
					{
$my_CashCredit->currency = $my_CashCredit->currency - $CashIn_value;//Вычли сумму поступления в кассу из суммы подотчёта сдающего в кассу.
$my_cashbox->cashcredit = $my_cashbox->cashcredit - $CashIn_value;//Вычли сумму поступления в кассу из суммы подотчёта по этой кассе.
$my_CashCredit->save();

					}//закрываем else if ($my_CashCredit->currency < $CashIn_value)
				}//закрываем if ($count_my_CashCredit == $one)
else 				//А если не нашёлся ни один подотчёт вносящего или подотчётов больше одного
				{
$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_CashIn',
		'action' => 'EditView',
		'record' => $whatever,
);				//Отменяем ввод
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
				}//закрываем else if ($count_my_CashCredit == $one)
}//закрываем if ($CashIn_type == 'cashback')

/*Если тип внесения - Оплата покупателей - увеличить cashdebet Кассы.
*/
if ($CashIn_type == 'MoneyIn')
{
$my_cashbox->cashdebet = $my_cashbox->cashdebet + $CashIn_value;
}
//////////////////////////////////////////////////////////////////////////
//Увеличить сумму в найденной кассе на сумму поступления в кассу.
$rel_cashbox = 'fintr_cashbox_fintr_cashin';//Мы знаем имя связи 
$my_cashbox->load_relationship($rel_cashbox);
			if ($my_cashbox->isOwner($CashIn_user))//Ещё раз проверили, что запись создаёт кассир
						{
$my_cashbox->currency = $my_cashbox->currency  + $CashIn_value;//Увеличили сумму в найденной кассе на сумму поступления в кассу.

$my_cashbox->$rel_cashbox->add($CashIn_id);//Связали Кассу и Поступление в кассу
$focus->status = 'closed';//Уставновили метку. Запись 'закрыта' для редактирования суммы поступления в Кассу
$focus->sum_const = $CashIn_value;//Сохранили 'неизменяемую' сумму поступления в Поступление в кассу
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

						}//закрываем else if ($my_cashbox->isOwner($CashIn_user))
			}//закрываем else if ($CashIn_status == 'closed')

		}

	}

?>
