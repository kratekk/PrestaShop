<?php
/**
*
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    Dotpay Team <tech@dotpay.pl>
*  @copyright Dotpay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*/


class dotpaycallbackModuleFrontController extends ModuleFrontController
{


	public function GetReference($id_customer,$id_cart,$secure_key){
			if($id_cart != '' && $id_customer !='' && $secure_key != ''){
					$orders_reference_sql = Db::getInstance()->getRow("SELECT `reference` as numer_zam FROM "._DB_PREFIX_."orders WHERE id_customer='".(int)($id_customer)."' AND id_cart = '".(int)($id_cart)."' AND secure_key='".$secure_key."' ");	
			 if($orders_reference_sql)	
				return $orders_reference_sql['numer_zam'];
			}else{
				return false;	
			}	
		}
	
		


   public function displayAjax()
    {

        
	if($_SERVER['REMOTE_ADDR'] == '77.79.195.34' and $_SERVER['REQUEST_METHOD'] == 'GET')
            die("PrestaShop - M.Ver: ".$this->module->version.", P.Ver: ". _PS_VERSION_ .", ID: ".Configuration::get('DP_ID_OC_MAIN').", Active: ".Configuration::get('DOTPAY_CONFIGURATION_OK_OC_MAIN').", Test: ".Configuration::get('DP_TEST_OC_MAIN').", CHK: ".Configuration::get('DP_CHK_OC_MAIN').", HTTPS: ".Configuration::get('DP_SSL_OC_MAIN').", P.SSL: ".Configuration::get('PS_SSL_ENABLED'));        
        
		if($_SERVER['REMOTE_ADDR'] <> '195.150.9.37')
			die("PrestaShop - ERROR (REMOTE ADDRESS: ".$_SERVER['REMOTE_ADDR'].")"); 	  
		
		if ($_SERVER['REQUEST_METHOD'] <> 'POST')
		    die("PrestaShop - ERROR (METHOD <> POST)");  	
			
		if (strlen(Configuration::get('DP_ID_OC_MAIN')) > 5  && (trim(Tools::getValue('channel')) !='' && trim(Tools::getValue('channel')) <> '73' ) ){ //nie sprawdzaj dla BLIK (brak parametru blik_code do porownania)
			if(!dotpay::check_urlc_dev())
            die("PrestaShop - SIGNATURE ERROR - CHECK PIN");		
		
		}
		
			
			
			
			$control_all = explode('|', trim(Tools::getValue('control')));
			$control_c = (int)$control_all[0];
			$i_suma_c = $control_all[1];
			$i_reference_c = $control_all[2];
			

			$cart = new Cart($control_c);
			$customer = new Customer((int)$cart->id_customer);


			
			$currency = Currency::getCurrency((int)$cart->id_currency);
			$currency_self = Currency::getCurrency((int)self::$cart->id_currency);
			$total = (float)$cart->getOrderTotal();
			
			$kw_zam = (float)$cart->getOrderTotal(true, Cart::BOTH);	

			
			$c_order = Db::getInstance()->getRow('SELECT `i_id_order`,`i_amount`,`i_currency`,`cookie_checksum`,`i_id_customer`,`i_suma`,`reference` FROM `'._DB_PREFIX_.'dotpay_amount_MAIN` WHERE `i_id_order`="'.$control_c.'"  AND `i_suma`="'.$i_suma_c.'"  AND `i_secure_key`="'.$customer->secure_key.'" ');
			
					$ver_kwota1 = $c_order['i_amount'];
					$ver_kwota = number_format($ver_kwota1, 2,'.', '');
					$ver_waluta = $c_order['i_currency'];
					$ver_suma = $c_order['i_suma'];
					$ver_chsum = $c_order['cookie_checksum'];
					$ver_custumer = $c_order['i_id_customer'];
					$new_ref = $c_order['reference'];
					
					$saved_amount = $ver_kwota." ".$ver_waluta;

			// dev	
				if (strlen(Configuration::get('DP_ID_OC_MAIN')) > 5){
				
					$orginal_amount = trim(Tools::getValue('operation_original_amount')); //e.g. 42.82
					$orginal_currency = trim(Tools::getValue('operation_original_currency')); //e.g. PLN
					$D_amount = trim(Tools::getValue('operation_amount'));
					
					$orginal_kwota = $orginal_amount." ".$orginal_currency;
					$Dotpay_transaction_id = trim(Tools::getValue('operation_number'));
					$Dotpay_operation_status = trim(Tools::getValue('operation_status'));		
				}

				
if(isset($ver_custumer) && $ver_custumer != ''){							


	if($orginal_kwota <> $saved_amount) 
		die('PrestaShop - NO MATCH OR WRONG CURRENCY - '.$orginal_kwota.' <> '.$saved_amount);	


	if($i_suma_c <> $ver_suma) 
		die('PrestaShop - NO MATCH OR WRONG ORDER SUM: '.$ver_suma.' <> '.$i_suma_c);

		
}else{

/*
	* if tmp order was deleted from 'dotpay_amount_MAIN' table (e.g. after 2 weeks):
*/
				// if Orders currency is different from the main currency of the store or if the amount is different from the orders posted in Dotpay (conversion)
					if(($currency["iso_code"] <> $currency_self["iso_code"]) || ($orginal_amount <> $D_amount) ){  
						$price = number_format(($total*$currency["conversion_rate"]), 2,'.', ''); 
					
					
					}else{					
						$price = number_format($total, 2,'.', ''); 
					}	
					
					
					if( (round($price/10, 1)* 10) <> (round($orginal_amount/10, 1)* 10)) //jesli waluta gl. rozna od waluty zamownienia, fix dla roznic z przewalutowania
						die('PrestaShop - NO MATCH OR WRONG AMOUNT - '.$price.' <> '.$orginal_amount);

				
					if($currency["iso_code"] <> $orginal_currency) 
						die('PrestaShop - NO MATCH OR WRONG CURRENCY - '.$currency["iso_code"].' <> '.$orginal_currency);	
		

}
		
			
			
if (strlen(Configuration::get('DP_ID_OC_MAIN')) > 5){   
//dev
        switch (Tools::getValue('operation_status'))
        {
            case "new":
                $actual_state = Configuration::get('PAYMENT_DOTPAY_NEW_STATUS');
                break;
            case "completed":
                $actual_state = _PS_OS_PAYMENT_;
                break;
            case "rejected":
                $actual_state = _PS_OS_ERROR_;
                break;
            case "processing_realization_waiting":
                $actual_state = Configuration::get('PAYMENT_DOTPAY_NEW_STATUS');
                break;
            case "processing_realization":
                $actual_state = Configuration::get('PAYMENT_DOTPAY_NEW_STATUS');
            default:
                die ("PrestaShop - WRONG TRANSACTION STATUS");
        }	
}		


		//update transaction ID from Dotpay
		if(isset($Dotpay_transaction_id) && isset($Dotpay_operation_status) ) {							
				$orders_reference = $this->GetReference($ver_custumer,$control_c,$customer->secure_key);
				if(isset($i_reference_c) && trim($i_reference_c) != "" ){
						 Db::getInstance()->Execute("UPDATE "._DB_PREFIX_."order_payment set transaction_id = '".$Dotpay_transaction_id."' WHERE order_reference = '".$orders_reference."' and payment_method = 'dotpay' ");
					}
				
			}	

	
        if($cart->OrderExists() == false)
        {
			$this->module->validateOrder($cart->id, $actual_state, $saved_amount, $this->module->displayName, NULL, array(), (int)$cart->id_currency, false, $customer->secure_key);
			if($orders_reference != "")
				Db::getInstance()->Execute("UPDATE "._DB_PREFIX_."order_payment set transaction_id = '".$Dotpay_transaction_id."' WHERE order_reference = '".$orders_reference."' ");
			echo "OK";

        }
        else
        {
            $history = new OrderHistory();
            $history->id_order = Order::getOrderByCartId($control_c);
            $lastOrderState = OrderHistory::getLastOrderState($history->id_order);
            if($lastOrderState->id <> $actual_state)
            {
                $history->changeIdOrderState($actual_state, $history->id_order);
				$history->addWithemail(true);
				if($orders_reference != "")
					Db::getInstance()->Execute("UPDATE "._DB_PREFIX_."order_payment set transaction_id = '".$Dotpay_transaction_id."' WHERE order_reference = '".$orders_reference."' ");

            }
			echo "OK";

        }
			
		// clean table: delete old records from tmp table (older then 4 weeks) 
			Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'dotpay_amount_MAIN` WHERE  `i_datetime_update` < CURDATE() - INTERVAL 4 WEEK');
				
		
		
    }
}
