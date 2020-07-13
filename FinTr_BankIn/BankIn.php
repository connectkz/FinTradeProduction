<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class BankIn extends SugarBean
	{
function Add_BankIn ( &$focus, $event, $arguments)
		{
/************* ПОСТУПЛЕНИЕ В БАНК *****************
Если сумма меньше 1 - отменить ввод
Если Банковский счёт не указан - отменить ввод
Если тип поступления в банк - payment или moneyback или collateral и не указан контрагент - отменить ввод
Если тип поступления в банк - cash и не указан assigned_user - отменить ввод
Если статус == closed, не добавлять к Банковскому счёту
* 
* Увеличить сумму на счёте и платежи без доходов. 
* А увеличивать ли Кредит по контрагенту, или в Доходах? ДА, в Доходах!!!
* Уменьшить подотчёт , но по какой Кассе, если Касса не одна? Пока сделано для одной кассы
* 
* 
  BankIn
  * 
  * 
***********
*/

global $db,$dictionary,$beanList;
global $current_user;
$bank_info = print_r($focus, true);
//$GLOBALS['log']->fatal("Нет сделки  . $bank_info"); 
$one = 1;
$zero = 0;
$BankIn_value = $focus->currency;
$BankIn_cashcredit = $BankIn_value;
$BankIn_type = $focus->bankin_type;
$BankIn_sum_const = $focus->sum_const;
$BankIn_id = $focus->id;
$BankIn_status = $focus->status;//closed или new
$CashDebet_user_id = $focus->assigned_user_id;//id вносящего в Банк
$Bank_id = $focus->fintr_bank_fintr_bankinfintr_bank_ida;// id счёта
$Accounts_id = $focus->fintr_bankin_accountsaccounts_ida;// id контрагента

										// Если сумма меньше 1 - 
if  (($BankIn_value == '') OR ($BankIn_value < '1'))
			{
$GLOBALS['log']->fatal("Отменён ввод Поступления в банк, так как сумма меньше 1");
										// отменить ввод
	$queryParams = array(
        'action' => 'index',
		'module' => 'FinTr_BankIn',
		'return_action' => 'DetailView',
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
				}
else 			
			{
			}	

										//Если Банковский счёт не указан
if  ($Bank_id == '')
			{
				
$my_message = 'Отменён ввод Поступления в банк, так как не указан Банковский счёт';
//SugarView::errors[0]  => 'Отменён ввод Поступления в банк, так как не указан Банковский счёт';
SugarApplication::appendErrorMessage($my_message);//Не работает если действует logic_hook BankIn_MoneyIn_relationship

$GLOBALS['log']->fatal($my_message);
										// отменить ввод
	$queryParams = array(
        'action' => 'index',
		'module' => 'FinTr_BankIn',
		'return_action' => 'DetailView',
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
				}
else 			
			{	
			}

					//Если тип поступления в банк - payment или moneyback или collateral и не указан контрагент
if ((($BankIn_type == 'payment') OR ($BankIn_type == 'moneyback') OR ($BankIn_type == 'collateral')) AND ($Accounts_id == ''))
{
					// отменить ввод
$GLOBALS['log']->fatal("Отменён ввод Поступления в банк, так как не указан контрагент");	
	$queryParams = array(
        'action' => 'index',
		'module' => 'FinTr_BankIn',
		'return_action' => 'DetailView',
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
}

//Если тип поступления в банк - payment или moneyback или collateral и не указан контрагент
if (($BankIn_type == 'cash') AND ($CashDebet_user_id == ''))
{
					// отменить ввод
$GLOBALS['log']->fatal("Отменён ввод Поступления в банк, так как не указан Вносящий наличные в Банк");	
	$queryParams = array(
        'action' => 'index',
		'module' => 'FinTr_BankIn',
		'return_action' => 'DetailView',
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
}
					
//Если статус = closed, не добавлять к Банковскому счёту
if ($BankIn_status == 'closed')
			{
				$focus->currency = $BankIn_sum_const;	//и не менять сумму поступления в Банк
					//Но всё остальное может меняться, включая ТИП ВНЕСЕНИЯ!!! 
			}
else
			{	
			

$Bank = new FinTr_Bank();
$Bank->retrieve ($Bank_id);
$Bank->currency = $Bank->currency + $BankIn_value;

if (($BankIn_type == 'payment') OR ($BankIn_type == 'moneyback') OR ($BankIn_type == 'collateral')) 
{
$Bank->bankdebet = $Bank->bankdebet + $BankIn_value;
}

if ($BankIn_type == 'cash') {
/*
Найти Кассы (т.к. касс может быть больше одной) , из которых выданы деньги под отчёт
*/
$my_cashbox = new FinTr_cashbox();
$order_by = "";
$where_cashbox = "cashcredit > '0'";//В кассе подотчёт больше 0, 
$check_dates = false;
$show_deleted = 0;
$list_my_cashbox = $my_cashbox->get_full_list($order_by, $where_cashbox, $check_dates, $show_deleted);
//Если есть Кассы, у которых выданы деньги под отчёт, то проверяем суммы по подотчётникам
$count_my_cashbox = count($list_my_cashbox);
	if ($count_my_cashbox > $zero)	
				{
$my_cashbox = $list_my_cashbox[0];

//Снимем с подотчёта пользователя. Если он вносит не больше подотчёта
$my_CashCredit = new FinTr_CashCredit();
$order_by = "";
$where_user_id = "assigned_user_id = '$CashDebet_user_id'"; //
$check_dates = false;
$show_deleted = 0;
$list_my_CashCredit = $my_CashCredit->get_full_list($order_by, $where_user_id, $check_dates, $show_deleted);
$count_my_CashCredit = count($list_my_CashCredit);
if ($count_my_CashCredit == $one)	{
$my_CashCredit = $list_my_CashCredit[0];

if (($my_CashCredit->currency) < $BankIn_value) {
$GLOBALS['log']->fatal("Отменён ввод Поступления в банк, так как сумма вносимых наличных больше подотчёта сотрудника, вносящего в банк");	
$queryParams = array(
                'action' => 'index',
		'module' => 'FinTr_BankIn',
		'return_action' => 'DetailView',
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
}

}
if ((($my_cashbox->cashcredit) - $BankIn_cashcredit) >= $zero){
$my_cashbox->cashcredit = $my_cashbox->cashcredit - $BankIn_cashcredit;
$my_CashCredit->currency = $my_CashCredit->currency - $BankIn_value;
}
else {
$GLOBALS['log']->fatal("Отменён ввод Поступления в банк, так как сумма вносимых наличных больше подотчёта ЕДИНСТВЕННОЙ Кассы");	
//Если Касса не одна, то  сначала проверяются подотчётные суммы по всем кассам, и если сумма во всем кассам не меньше вносимого в банк -  ввод не отменяется
$queryParams = array(
                'action' => 'index',
		'module' => 'FinTr_BankIn',
		'return_action' => 'DetailView',
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
/*Строчки ниже - для случая, если касса НЕ ОДНА
$my_cashbox->cashcredit = $zero;
$BankIn_cashcredit = $BankIn_cashcredit - $my_cashbox->cashcredit;
}
*/

}
//$my_cashbox_id = $my_cashbox->id;	//нашли id Касы
$my_cashbox->save();
$my_CashCredit->save();

				}
}

$Bank->save();
$focus->status = 'closed';
$focus->sum_const = $focus->currency;

function BankIn_ListView()
{
$queryParams = array(
                'action' => 'index',
		'module' => 'FinTr_BankIn',
		'return_action' => 'DetailView',
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
}
			}//закрываем else if ($BankIn_status == 'closed')
		}

	}

?>
