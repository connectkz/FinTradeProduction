<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class CashIn_MoneyIn extends SugarBean
	{
function CashIn_MoneyIn_relationship ( &$focus, $event, $arguments)
		{
require_once('modules/Opportunities/Opportunity.php');
/************* ПОСТУПЛЕНИЕ В КАССУ СВЯЗЫВАЕТСЯ С ДОХОДАМИ *****************
Если статус Поступление в кассу == closed
 Если Поступление в кассу НЕ с типом 'оплата покупателей', то отменить ввод
 Если Поступление в кассу меньше суммы вносимого дохода, то отменить ввод
 Если Поступление в кассу с типом 'оплата покупателей' и сделка не указана, то отменить ввод
 Если Поступление в кассу меньше суммы вносимого дохода и всех ранее внесённых доходов, то отменить ввод
  Найти все ранее внесённые доходы
 Уменьшить в кассе сумму наличных без доходов на сумму Дохода
 Если Поступление в кассу с типом 'оплата покупателей' и Сумма всех доходов по данной сделке не превышает   Сумму сделки - увеличить Сумму доходов по данной сделке
 Связать Доход и Контрагента связанного со Сделкой
  
    /opt/sugarcrm/apps/sugarcrm/htdocs/modules/Opportunities/Opportunity.php
Иначе - ничего не делать!
********* 
*/
global $db,$dictionary,$beanList;
global $current_user;
$one = 1;
$zero = 0;

$CashIn_type = $focus->type;//Тип поступления в Кассу - Оплата, Возврат подотчётника, прочее
$CashIn_status = $focus->status;//closed - значит доход привязываем к существующему Поступлению в кассу
$CashIn_currency = $focus->currency;
$CashIn_id = $arguments[id];
$current_MoneyIn_id = $arguments[related_id];

$current_MoneyIn_money_in = $focus->fintr_cashin_fintr_moneyin->tempBeans[$current_MoneyIn_id]->money_in;
$current_MoneyIn_opportunities_name = $focus->fintr_cashin_fintr_moneyin->tempBeans[$current_MoneyIn_id]->fintr_moneyin_opportunities_name;//имя сделки
$current_MoneyIn_opportunities_id = $focus->fintr_cashin_fintr_moneyin->tempBeans[$current_MoneyIn_id]->fintr_moneyin_opportunitiesopportunities_ida;//id сделки

//$beanList_print = print_r($beanList, true);
$GLOBALS['log']->fatal("СТАРТ"); 
//CashIn_ListView ();

//Если статус Поступление в кассу == closed, т.е. хук вызван НЕ созданием Поступления в кассу
if ($CashIn_status == 'closed')
{

//Если Поступление в кассу НЕ с типом 'оплата покупателей', то отменить ввод
if ($CashIn_type != 'MoneyIn')
{
$GLOBALS['log']->fatal("Отменён ввод Дохода так как поступление в кассу - не оплата покупателей"); 
CashIn_ListView ();
}

//Если Поступление в кассу меньше суммы вносимого дохода, то отменить ввод
if ($CashIn_currency < $current_MoneyIn_money_in)
{
$GLOBALS['log']->fatal("Отменён ввод Дохода так как Поступление в кассу меньше суммы вносимого дохода"); 
CashIn_ListView ();
}

//Если Поступление в кассу с типом 'оплата покупателей' и сделка не указана, то отменить ввод
//Пока отменяем ввод. Но нужно ставить задачу о прикручивании сделки к доходу
if (($CashIn_type == 'MoneyIn') AND (!$current_MoneyIn_opportunities_name))
{
$GLOBALS['log']->fatal("Нет сделки  .$current_MoneyIn_opportunities_name"); 
CashIn_ListView ();
}

//Если Поступление в кассу меньше суммы вносимого дохода и всех ранее внесённых доходов, то отменить ввод
/// Найти все ранее внесённые доходы связанные с текущим Поступлением в кассу
$rel_name = 'fintr_cashin_fintr_moneyin';// Мы знаем имя связи между Поступлением в кассу и Доходом
$focus->load_relationship($rel_name);
$MoneyIn_list = array();
foreach ($focus->$rel_name->getBeans(new FinTr_MoneyIn()) as $MoneyIns) {
    $MoneyIn_list[$MoneyIns->id] = $MoneyIns;
$MoneyIn_sum = $MoneyIn_sum + $MoneyIns->money_in;//получаем сумму всех доходов связанных с этим Поступлением в кассу
}
if ($CashIn_currency < $MoneyIn_sum)
{
$GLOBALS['log']->fatal("Отменён ввод Дохода так как Поступление в кассу меньше суммы вносимого дохода и всех ранее внесённых доходов"); 
CashIn_ListView ();
}

//Уменьшить в Кассе сумму наличных без доходов на сумму Дохода
///Найти Кассу связанную с текущим Поступлением в кассу
///ЕСЛИ Создаваемый доход больше чем Сумма наличных без доходов - отменить ввод

$rel_cashbox = 'fintr_cashbox_fintr_cashin';//Мы знаем имя связи 
$focus->load_relationship($rel_cashbox);
$cashbox_list = array();
foreach ($focus->$rel_cashbox->getBeans(new FinTr_cashbox()) as $cashbox) {
    $cashbox_list[$cashbox->id] = $cashbox;
}
$count_cashbox = count($cashbox_list);
if ($count_cashbox > $zero)//Если нашлась хоть одна касса. Должна найтись. Проверяется в /opt/sugarcrm/apps/sugarcrm/htdocs/custom/modules/FinTr_CashIn/CashIn.php
	{
	if ($current_MoneyIn_money_in > $cashbox->cashdebet)//ЕСЛИ Создаваемый доход больше чем Сумма наличных без доходов
		{
$GLOBALS['log']->fatal("Отменён ввод Дохода так как Создаваемый доход больше чем Сумма наличных без доходов");
		CashIn_ListView ();// - отменить ввод
		}
$cashbox->cashdebet = $cashbox->cashdebet - $current_MoneyIn_money_in;
$cashbox->save();
	}
/*
//Если Сумма всех доходов по данной сделке не превышает Сумму сделки - увеличить Сумму доходов по данной сделке  moneyin_c
//$current_MoneyIn_opportunities_id - id сделки
//Как сохранить custom-поле в стандартном модуле $bean->custom_fields->save()
* Могут быть поля Дебет и Кредит по каждой сделке.
*/
$Opportunity = new Opportunity();
$Opportunity->retrieve($current_MoneyIn_opportunities_id);
$Opportunity->save(); 
$rel_MoneyIn = 'fintr_moneyin_opportunities';
$Opportunity->load_relationship($rel_MoneyIn);
$MoneyIn_Oppotunity_list = array();
foreach ($Opportunity->$rel_MoneyIn->getBeans(new FinTr_MoneyIn()) as $MoneyIns) {
    $MoneyIn_Oppotunity_list[$MoneyIns->id] = $MoneyIns;
$MoneyIn_Oppotunity_sum = $MoneyIn_Oppotunity_sum + $MoneyIns->money_in;//получаем сумму всех доходов связанных с этой Сделкой
}
$GLOBALS['log']->fatal("Сумма доходов по сделке . $MoneyIn_Oppotunity_sum");
$Opportunity_real_money_c = $Opportunity->real_money_c;
if ($MoneyIn_Oppotunity_sum  > $Opportunity->amount)
		{
$GLOBALS['log']->fatal("Отменён ввод Дохода так как Сумма всех доходов по данной сделке превышает Сумму сделки");
			CashIn_ListView ();// - отменить ввод
		}
$Opportunity->real_money_c = $MoneyIn_Oppotunity_sum;
$Opportunity_real_money_c = $Opportunity->real_money_c;
$Opportunity_expenses = $Opportunity->real_expenses_c;//Сумма расходов по сделке
$Opportunity->profit_c = $Opportunity_real_money_c - $Opportunity_expenses;//Получаем прибыль по сделке
$Opportunity_info = print_r($Opportunity, true);
$Opportunity->save();

//Связать Доход и Контрагента связанного со Сделкой
$Account_id = $Opportunity->account_id;
$GLOBALS['log']->fatal("ZZZ  . $Account_id");
$AccountZ = new Account();
$AccountZ->retrieve($Account_id);
$rel_Account_MoneyIn  = 'fintr_moneyin_accounts';
$MoneyIns = $MoneyIn_list[$current_MoneyIn_id];
$MoneyIns->load_relationship($rel_Account_MoneyIn);
$MoneyIns->$rel_Account_MoneyIn->add($Account_id);
$MoneyIns->money_in_via = 'cash';//Значит, доход поступил наличными. значит, убрать из субпанели поле 
$MoneyIns->save();

$AccountZ->gross_profit_c = $AccountZ->gross_profit_c + $current_MoneyIn_money_in; //увеличиваем доход от контрагента
$AccountZ->credit_c = $AccountZ->credit_c + $current_MoneyIn_money_in; //увеличиваем нашу задолженность перед контрагентом
																		//А увеличиваем прибыль от контрагента???
$AccountZ->save();
/*

*/


//$subpanel_info = print_r($layout_defs, true);
//$GLOBALS['log']->fatal("Субпанель  .$subpanel_info"); 
}
// ******************************************************************
function CashIn_ListView ()
{
$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_CashIn',
		'action' => 'ListView',
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
}

		}

	}

?>
