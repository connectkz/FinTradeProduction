<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class CashOut_MoneyOut extends SugarBean
	{
function CashOut_MoneyOut_relationship ( &$focus, $event, $arguments)
		{
require_once('modules/Opportunities/Opportunity.php');
/************* ВЫДАЧА ИЗ КАССЫ СВЯЗЫВАЕТСЯ С РАСХОДАМИ *****************
Расходы
Если status = closed, то 	{  
  Если сумма расхода больше суммы выдачи из кассы - отменить ввод.
  * Если Выдача из кассы меньше суммы Расхода и всех ранее внесённых Расходов, то отменить ввод
     Найти все ранее внесённые Расходы
  Если сумма расхода больше суммы у подотчётника - отменить ввод. !!! Ха-ха, до этой проверки никогда не дойдёт дело. 
  
 Если Статья расходов (resolution) Products или Materials, и есть сделка, то отмениь ввод. Материалы и товары напрямую не ложим расходами по сделке.
	
 Если Статья расходов (resolution) Products или Materials - Статус (stage) = open, иначе Статус (stage) = closed
 
  

 Уменьшить в Кассе сумму денег под отчёт. Уменьшить сумму у подотчётника
  Если сделка есть - связать расход со Сделкой и с Контрагентом-клиентом. И если товар - это одно, а если взятки - другое... Дебет/кредит
  * 
  * 
  * 
  
							}  
Иначе - ничего не делать!CashOut_MoneyOut
********* 
*/
global $db,$dictionary,$beanList;
global $current_user;
$one = 1;
$zero = 0;

$CashOut_status = $focus->status; //closed - значит расход привязываем к существующей выдаче наличных
$CashOut_currency = $focus->currency; // СУММА выданных денег
$CashOut_user = $focus->assigned_user_id; //id подотчётника, которому выданы деньги из кассы
$CashOut_id = $arguments[id];//
$current_MoneyOut_id = $arguments[related_id];//ID Расхода
$current_MoneyOut_resolution = $focus->fintr_cashout_fintr_moneyout->tempBeans[$current_MoneyOut_id]->resolution;//Статья расходов
$current_MoneyOut_currency = $focus->fintr_cashout_fintr_moneyout->tempBeans[$current_MoneyOut_id]->currency;//Сумма создаваемого расхода
$current_MoneyOut_currency = $focus->fintr_cashout_fintr_moneyout->tempBeans[$current_MoneyOut_id]->fintr_moneyout_opportunitiesopportunities_ida;//Сделка

$CashOut = print_r($focus, true);
$GLOBALS['log']->fatal("РАСХОДЫ . $CashOut");

/*
function CashOut_ListView ()
{
$queryParams = array(
                'action' => 'ajaxui#ajacUILoc=index.php',
		'module' => 'FinTr_CashOut',
		'action' => 'ListView',
);
SugarApplication::redirect('index.php?' . http_build_query($queryParams));
}
*/
		}

	}

?>
