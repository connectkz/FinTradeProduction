<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class BankIn_MoneyIn extends SugarBean
	{
function BankIn_MoneyIn_relationship ( &$focus, $event, $arguments)
		{
require_once('modules/Opportunities/Opportunity.php');
/************* ПОСТУПЛЕНИЕ В БАНК СВЯЗЫВАЕТСЯ С ДОХОДАМИ ***************** FinTr_BankIn
Если статус Поступления в банк = closed
 Если сумма дохода меньше 1 - отменить ввод
 * Если тип поступления - внесение наличных, то отменить ввод. Вообще - надо убирать субпанель в этом случае.
 * Если сделка не указана - отменить ввод.
 * Если поступление в банк меньше суммы добавляемого дохода и всех ранее внесённых доходов - отменить ввод.
 * Если сумма всех доходов по сделке и создаваемого дохода превышает сумму сделки - отменить ввод.
 * Если поступление в банк - от одного контрагента, а сделка с другим контрагентом - отменить ввод. Или, отдельный checkbox поставить. Ведь бывает такое

 * Увеличить сумму доходов по сделке
 * Увеличить Прибыль по сделке
 * Уменьшить сумму платежей без доходов по данному счёту. 
 * Связать Доход и Контрагента связанного со Сделкой
 * Увеличиваем доход от Контрагента
 * Увеличиваем нашу задолженность перед Контрагентом
Иначе - отменить всё!
*/

$one = 1;
$zero = 0;

global $db,$dictionary,$beanList;
global $current_user;


$BankIn_type = $focus->bankin_type; //Тип поступления в Банк - Оплата, Внесение наличных, Возврат от поставщика...
$BankIn_status = $focus->status; //closed - значит доход привязываем к существующему Поступлению в Банк
$BankIn_currency = $focus->currency; // СУММА
$BankIn_id = $arguments[id];
$Bank_id = $focus->fintr_bank_fintr_bankinfintr_bank_ida; //id банковского счёта. 
$Account_BankIn_id = $focus->fintr_bankin_accountsaccounts_ida; // id Контрагента, вносившего на счёт.
$current_MoneyIn_id = $arguments[related_id];//ID Дохода
$current_MoneyIn_money_in = $focus->fintr_bankin_fintr_moneyin->tempBeans[$current_MoneyIn_id]->money_in;//Сумма вносимого дохода
$current_MoneyIn_opportunities_name = $focus->fintr_bankin_fintr_moneyin->tempBeans[$current_MoneyIn_id]->fintr_moneyin_opportunities_name;//имя сделки
$current_MoneyIn_opportunities_id = $focus->fintr_bankin_fintr_moneyin->tempBeans[$current_MoneyIn_id]->fintr_moneyin_opportunitiesopportunities_ida;//id сделки
$current_MoneyIn_crossmoney = $focus->fintr_bankin_fintr_moneyin->tempBeans[$current_MoneyIn_id]->crossmoney;//
//$MoneyIn = print_r($focus, true);
//$GLOBALS['log']->fatal("Доход . $MoneyIn"); 
if ($BankIn_status == 'closed')
{

$GLOBALS['log']->fatal("Создание дохода на основании входящего банковского платежа "); 

//Если Поступление в Банк с типом 'cash', т.е. внесение наличных, то отменить ввод. Доходы от постпления налияных создаются в Кассе
if ($BankIn_type == 'cash')
{
$GLOBALS['log']->fatal("Отменён ввод Дохода так как поступление в Банк - внесение наличных"); 
$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_BankIn',
		'action' => 'ListView',
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
//


}
//Если сделка не указана - отменить ввод. Пока отменяем ввод. Но нужно ставить задачу о прикручивании сделки к доходу
if ((!$current_MoneyIn_opportunities_name))
{
//	echo '<script type="text/javascript"> alert ('ЗАБАЗЯКА') </script>';

//$my_message = 'ЗАРАЗА';
//ListView::setHeaderTitle($my_message);

//SugarApplication::appendErrorMessage($my_message);

$GLOBALS['log']->fatal("Нет сделки  .$current_MoneyIn_opportunities_name"); 
$queryParams = array(
                'action' => 'index.php',
		'module' => 'FinTr_BankIn',
		'action' => 'ListView',
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
}
//Если поступление в банк меньше суммы добавляемого дохода и всех ранее внесённых доходов - отменить ввод.
$rel_name = 'fintr_bankin_fintr_moneyin';// Мы знаем имя связи между Поступлением в кассу и Доходом
$focus->load_relationship($rel_name);
$MoneyIn_list = array();
foreach ($focus->$rel_name->getBeans(new FinTr_MoneyIn()) as $MoneyIns) {
    $MoneyIn_list[$MoneyIns->id] = $MoneyIns;
$MoneyIn_sum = $MoneyIn_sum + $MoneyIns->money_in;//получаем сумму всех доходов связанных с этим Поступлением в кассу
}
if ($BankIn_currency < $MoneyIn_sum)
{
$GLOBALS['log']->fatal("Отменён ввод Дохода так как Поступление в Банк меньше суммы вносимого дохода и всех ранее внесённых доходов"); 
$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_BankIn',
		'action' => 'ListView',
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
}
//Если Сумма всех доходов по данной сделке не превышает Сумму сделки - увеличить Сумму доходов по данной сделке, Прибыль по этой сделке
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
 
$Opportunity_real_money_c = $Opportunity->real_money_c;
if ($MoneyIn_Oppotunity_sum  > $Opportunity->amount)
		{
$GLOBALS['log']->fatal("Отменён ввод Дохода так как Сумма всех доходов по данной сделке превышает Сумму сделки");
			CashIn_ListView ();// - отменить ввод
		}
$Opportunity->real_money_c = $MoneyIn_Oppotunity_sum; //Увеличить сумму доходов по сделке
$Opportunity_real_money_c = $Opportunity->real_money_c;
$Opportunity_expenses = $Opportunity->real_expenses_c;//Сумма расходов по сделке
$Opportunity->profit_c = $Opportunity_real_money_c - $Opportunity_expenses;//Увеличить Прибыль по сделке
$Opportunity_info = print_r($Opportunity, true);
$Opportunity->save();
 
$Bank = new FinTr_Bank ();
$Bank->retrieve($Bank_id);
$Bank_bankdebet = $Bank->bankdebet;
if ($Bank_bankdebet >= $current_MoneyIn_money_in) {
$Bank->bankdebet = $Bank_bankdebet - $current_MoneyIn_money_in;// Уменьшить сумму платежей без доходов по данному счёту.
$Bank->save();
}

$Account_Opportunity_id = $Opportunity->account_id;//id Контрагента, связанного со сделкой
//Если поступление в банк - от одного контрагента, а сделка с другим контрагентом
$GLOBALS['log']->fatal("$Account_Opportunity_id . $Account_BankIn_id");
if ((($Account_Opportunity_id) != ($Account_BankIn_id)) AND (!$current_MoneyIn_crossmoney)) {
$GLOBALS['log']->fatal("Отменён ввод Дохода так как Платёж в банк от одного контрагента, а сделка с другим, и не поставлен соответствующий чекбокс");
$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_BankIn',
		'action' => 'ListView',
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
}

$Account = new Account();
$Account->retrieve($Account_Opportunity_id);//Доходы связваем с Контрагентом, с которым сделка, а не с плательщиком в банк
$rel_Account_MoneyIn  = 'fintr_moneyin_accounts';
$MoneyIns->load_relationship($rel_Account_MoneyIn);
$MoneyIns->$rel_Account_MoneyIn->add($Account_Opportunity_id);
$MoneyIns->save();

$Account->gross_profit_c = $Account->gross_profit_c + $current_MoneyIn_money_in; //увеличиваем доход от контрагента
$Account->credit_c = $Account->credit_c + $current_MoneyIn_money_in; //увеличиваем нашу задолженность перед контрагентом
																	//А увеличиваем прибыль от контрагента???
$Account->save();
}
else {
$GLOBALS['log']->fatal("BankIn_MoneyIn запущен из BankIn"); 
}
/*
function BankIn_ListView ()
{
$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_BankIn',
		'action' => 'ListView',
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
}
*/
		}

	}

?>
