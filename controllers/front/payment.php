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

class dotpaypaymentModuleFrontController extends ModuleFrontController
{ 
	
/**
     * Gennerate a unique reference for orders generated with the same cart id
     * This references, is usefull for check payment
     *
     * @return String
     */
	

	
	public function getChannelInfo($ch_number,$what){
			if(is_numeric($ch_number)){
				$channel_sql = Db::getInstance()->getRow("SELECT `channel_name`, `logo_url` FROM "._DB_PREFIX_."dotpay_channel_MAIN WHERE channel_number=".(int)$ch_number);	
				if($what !='' && $what == 'name'){
					$info_channel = $channel_sql['channel_name'];
				}
				elseif($what !='' && $what == 'logo'){
					$info_channel = $channel_sql['logo_url'];
				}else{
					return false;
				}
			 if(count($channel_sql) >1)	
				return $info_channel;
			}else{
			return false;
			
			}
				
		}

/*	
 # Get Agreement from Dotpay
 #	$what : 'bylaw'|'personal_data'
*/ 

	public	function DotpayAgreement($what){
		
			global $cookie, $cart;
			
			if (Configuration::get('DP_TEST_OC_MAIN') == 1 && strlen(Configuration::get('DP_ID_OC_MAIN')) > 5) { $url1_channel = "test_payment/";}
			elseif (strlen(Configuration::get('DP_ID_OC_MAIN')) > 5 && Configuration::get('DP_TEST_OC_MAIN') != 1) { $url1_channel = "t2/"; }
			else { $url1_channel = "/"; }

			$language_tmp = strtolower(LanguageCore::getIsoById((int)$cookie->id_lang));
			$currency1 = Currency::getCurrency($cart->id_currency);

			$url = "https://ssl.dotpay.pl/test_payment/payment_api/channels/?currency=".$currency1["iso_code"]."&id=".Configuration::get('DP_ID_OC_MAIN')."&amount=301.00&lang=".$language_tmp;
			$ch = curl_init();
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_REFERER, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				$result = curl_exec($ch);
			curl_close($ch);
			$obj3 = json_decode($result,true);
			
			foreach($obj3['forms'] as $forms){
			
				foreach($forms['fields'] as $forms1){
					if($forms1['name'] == $what){
						$wynik = $forms1['description_html'];
						}
					}		
				}	
			
			if($wynik !=''){ 
					return $wynik;
				}else{ 
					return false;
				}
		
		}
	
	/*	
 # Get last id_order to redirect
 #	
*/ 	
		public function GetlastID($secure_key){
				if($secure_key != ''){
						$orders_lastid_sql = Db::getInstance()->getRow("SELECT MAX(id_order) as last_id FROM "._DB_PREFIX_."orders WHERE secure_key='".$secure_key."' ");	
				 if($orders_lastid_sql)	
					return $orders_lastid_sql['last_id'];
				}else{
					return false;	
				}	
			}
	
		
  
    public function initContent()
    {
        $this->display_column_left = false;	
        parent::initContent();
        $control=(int)Tools::getValue('control');
        $cart = $this->context->cart; 
		$cookie = $this->context->cookie;
				
        if (!empty($control))
            $cart = new Cart($control);
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
                Tools::redirect('index.php?controller=order&step=1');
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer))
                Tools::redirect('index.php?controller=order&step=1');
        $address = new Address($cart->id_address_invoice);

		
        $params = null;
        $template = "payment_return";
        if ($cart->OrderExists() == true) 
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.Order::getOrderByCartId($cart->id).'&key='.$customer->secure_key);                    
        elseif (Tools::getValue("status") == "OK")
            $form_url = $this->context->link->getModuleLink('dotpay', 'payment', array('control' => $cart->id, 'status' => 'OK'), Configuration::get('DP_SSL_OC_MAIN', false));
        else 
        {
            // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
            $authorized = false;
            foreach (Module::getPaymentModules() as $module)
            if ($module['name'] == 'dotpay')
            {
                $authorized = true;
                break;
            }
            if (!$authorized)
                die('This payment method is not available.');         
            $template = "payment";           
            $currency = Currency::getCurrency($cart->id_currency);
			$form_url1 = "";	
				if (Configuration::get('DP_TEST_OC_MAIN') == 1 && (strlen(Configuration::get('DP_ID_OC_MAIN'))) > 5) $form_url1="test_payment/";
				if (strlen(Configuration::get('DP_ID_OC_MAIN')) > 5 && Configuration::get('DP_TEST_OC_MAIN') != 1) $form_url1="t2/";
			$form_url = "https://ssl.dotpay.pl/".$form_url1;
			
			
			
			//save amount to tmp table
			 $i_amount = (float)$cart->getOrderTotal(true, Cart::BOTH);
				
				$i_id_customer = null;
				if ($this->context->customer->isLogged()) {
					 $i_id_customer = $this->context->customer->id;
				}else{
					 $i_id_customer = $this->context->cookie->id_customer;	
				}
				
			$cookie_checksum = (int) $cookie->checksum;
			$cookie_is_guest = (int) $cookie->is_guest;
			$cookie_id_guest = (int) $cookie->id_guest;
			$cookie_id_connections = (int) $cookie->id_connections;
				
				$sum_order_cust = md5($cart->id.$i_amount.$currency["iso_code"].$cookie_checksum.$i_id_customer.$cookie_is_guest.$cookie_id_guest.$cookie_id_connections);

			
			$order_reference_update = Db::getInstance()->getRow('SELECT `i_suma` FROM '._DB_PREFIX_.'dotpay_amount_MAIN WHERE `i_suma` = "'.$sum_order_cust.'" ');		
			if (!$order_reference_update['i_suma']) {	
			Db::getInstance()->Execute('INSERT INTO '._DB_PREFIX_.'dotpay_amount_MAIN (i_id_order,i_amount,i_currency,cookie_checksum,i_id_customer,i_suma,i_is_guest,i_id_guest,i_id_connections,i_secure_key) '.'VALUES("'.$cart->id.'","'.$i_amount.'","'.$currency["iso_code"].'","'.$cookie_checksum.'","'.$i_id_customer.'","'.$sum_order_cust.'","'.$cookie_is_guest.'","'.$cookie_id_guest.'","'.$cookie_id_connections.'","'.$customer->secure_key.'")');
			
			}
			
			
			
			
			$language = strtolower(LanguageCore::getIsoById($cookie->id_lang));
			
			if($language == 'pl'){
				$lang_desc = ", numer zamówienia: ";
				$lang_company = ", firma: ";
				$lang_other = ", dodatkowe informacje: ";
			}else{
				$lang_desc = ", order number: ";
				$lang_company = ", company: ";
				$lang_other = ", other info: ";
			}
			

			
				
				$iso_lang = new Country((int)($address->id_country));
							
				$tel_nr ='';
				if($address->phone_mobile !='') $tel_nr = $address->phone_mobile;
				if($address->phone !='') $tel_nr = $address->phone;
				if($address->company !='') {$firma = $lang_company.$address->company.' ';}else{$firma ='';}
				if($address->other !='') {$inne = $lang_other.$address->other.' ';}else{$inne ='';}			
				
                
            if(Configuration::get('DP_CHANNELS_VIEW_MAIN') == 1 || Configuration::get('DP_CHANNELS_VIEW_MAIN') == 2){
                    $type_OC_MAIN = 4;
					$ch_lock_OC_MAIN = 1;
					$ch_NR_MAIN = '';
					$ch_chk = '';
					
                }
            
            else{
                
				$ch_chk = $params["channel"];
				
				if (Configuration::get('DP_CHANNELS_VIEW_MAIN') == 4 && Configuration::get('DP_ONE_CHANNEL_SELECTED_MAIN') !='' ){   
					$type_OC_MAIN = 4;
					$ch_lock_OC_MAIN = 1;
					$ch_NR_MAIN = Configuration::get('DP_ONE_CHANNEL_SELECTED_MAIN');

				}else{
					$type_OC_MAIN = 0;
					$ch_lock_OC_MAIN = '';
					$ch_NR_MAIN = '';
				}
				
              }

			  	if (Configuration::get('DP_SUMMARY_OC_MAIN') != 1 || Configuration::get('DP_CHANNELS_VIEW_MAIN') <> 3) 
					{
						$bylaw_var = 1;
						$personal_data_var = 1;
					}else {
						$bylaw_var = '';
						$personal_data_var = '';
					}
			  
	//##  add and save new order --------------		
			if($cart->id == true)
			{	
					
					if (Configuration::get("DP_REFERENCE_MAIN") == 1) {
						
						$dotpay_o = new dotpay();
						
						$kwota_zam2 = (float)$cart->getOrderTotal(true, Cart::BOTH)." ".$currency["iso_code"];
						$dotpay_o->validateOrder($cart->id, Configuration::get('PAYMENT_DOTPAY_NEW_STATUS_UNPAID'), $kwota_zam2, $dotpay_o->displayName, NULL, array(), NULL, false, $customer->secure_key);
							$order_id = Order::getOrderByCartId(intval($cart->id));
									$order = new Order($order_id);
									$result_ref = $order->reference;
									$nr_zam2 = $order_id." (Ref.: ".$order->reference.")";

					//update reference
					$order_reference_update = Db::getInstance()->getRow('SELECT `reference`, `i_suma` FROM '._DB_PREFIX_.'dotpay_amount_MAIN WHERE `i_suma` = "'.$sum_order_cust.'" ');		
						if ($order_reference_update['i_suma']) {
							Db::getInstance()->Execute("UPDATE "._DB_PREFIX_."dotpay_amount_MAIN set reference = '".$result_ref."' WHERE `i_suma` = '".$sum_order_cust."' ");
						}			
								
					}else{
									$nr_zam2 = $cart->id;
									$result_ref = "";
					
					}
			}else{
									$nr_zam2 = $this->GetlastID($customer->secure_key);
									$result_ref = "";
									
				Tools::redirectLink(__PS_BASE_URI__.'index.php?controller=order-detail&id_order='.$this->GetlastID($customer->secure_key));
			
			}

	//##  --------------	
				
			if ((strlen(Configuration::get('DP_ID_OC_MAIN'))) > 5) {				      
				
					// check and separate street and number from adress	
						if(trim($address->address1) !='' && trim($address->address2) == '' ){
								if (preg_match('/^(.+) (\d+([a-zA-Z0-9\/ _\-\.]+)?)?$/', trim($address->address1), $result1))
									{	
										if(count($result1) < 3 ){
											$ulica = trim($address->address1);
											$numer = "";
										}
										else{
											$ulica = $result1[1];
											$numer = str_replace('.', '', $result1[2]);
										}			
									}else{
										$ulica = trim($address->address1);
										$numer = "";
									}
						}else{
						
								$ulica = trim($address->address1);
								$numer = trim($address->address2);
						}

			if(Configuration::get('DP_CHANNELS_VIEW_MAIN') == 1 || Configuration::get('DP_CHANNELS_VIEW_MAIN') == 2){
				
				$params = array(
                    'id' => Configuration::get('DP_ID_OC_MAIN'),
                    'amount' => (float)$cart->getOrderTotal(true, Cart::BOTH),
                    'currency' => $currency["iso_code"],
                    'description' => Configuration::get('PS_SHOP_NAME').$lang_desc.$nr_zam2.$firma.$inne,
                    'lang' => $language,
                    'ch_lock' => $ch_lock_OC_MAIN,
                    'URL' => $this->context->link->getModuleLink('dotpay', 'payment', array('control' => $cart->id), Configuration::get('DP_SSL_OC_MAIN', false)),                        
                    'type' => $type_OC_MAIN,                      
                    'buttontext' => '',                        
                    'URLC' => $this->context->link->getModuleLink('dotpay', 'callback', array('ajax' => '1'), Configuration::get('DP_SSL_OC_MAIN', false)),
                    'control' => $cart->id.'|'.$sum_order_cust.'|'.$result_ref,
                    'firstname' => $customer->firstname,
                    'lastname' => $customer->lastname,                        
                    'email' => $customer->email,
                    'street' => $ulica,
                    'street_n1' => $numer,
                    'street_n2' => '',
                    'state' => '',
                    'addr3' => '',
                    'city' => $address->city,
                    'postcode'=> $address->postcode,
                    'phone'=> $tel_nr,
                    'country'=> $iso_lang->iso_code,
                    'api_version' => 'dev'
					);
				 
				 }else{
					
					$params = array(
                    'id' => Configuration::get('DP_ID_OC_MAIN'),
                    'amount' => (float)$cart->getOrderTotal(true, Cart::BOTH),
                    'currency' => $currency["iso_code"],
                    'description' => Configuration::get('PS_SHOP_NAME').$lang_desc.$nr_zam2.$firma.$inne,
                    'lang' => $language,
                    'channel' => $ch_NR_MAIN,
                    'ch_lock' => $ch_lock_OC_MAIN,
                    'URL' => $this->context->link->getModuleLink('dotpay', 'payment', array('control' => $cart->id), Configuration::get('DP_SSL_OC_MAIN', false)),                        
                    'type' => $type_OC_MAIN,                        
                    'buttontext' => '',                        
                    'URLC' => $this->context->link->getModuleLink('dotpay', 'callback', array('ajax' => '1'), Configuration::get('DP_SSL_OC_MAIN', false)),
                    'control' => $cart->id.'|'.$sum_order_cust.'|'.$result_ref,
                    'firstname' => $customer->firstname,
                    'lastname' => $customer->lastname,                        
                    'email' => $customer->email,
                    'street' => $ulica,
                    'street_n1' => $numer,
                    'street_n2' => '',
                    'state' => '',
                    'addr3' => '',
                    'city' => $address->city,
                    'postcode'=> $address->postcode,
                    'phone'=> $tel_nr,
                    'country'=> $iso_lang->iso_code,
                    'api_version' => 'dev'
					); 
					 
				 }
				

			
				
		$chk = Configuration::get('DP_PIN_OC_MAIN').
				$params["api_version"].
				$params["lang"].
				$params["id"].
				$params["amount"].
				$params["currency"].
				$params["description"].
				$params["control"].
				$ch_chk.
				$params["ch_lock"].
				$params["URL"].
				$params["type"].
				$params["buttontext"].
				$params["URLC"].
				$params["firstname"].
				$params["lastname"].
				$params["email"].
				$params["street"].
				$params["street_n1"].
				$params["street_n2"].
				$params["state"].
				$params["addr3"].
				$params["city"].
				$params["postcode"].
				$params["phone"].
				$params["country"].
				$bylaw_var.
				$personal_data_var
				;		
				
			if(Configuration::get('DP_CHK_OC_MAIN') && (Configuration::get('DP_CHANNELS_VIEW_MAIN') == 3 || Configuration::get('DP_CHANNELS_VIEW_MAIN') == 4))
				$params['chk'] = hash('sha256', $chk);
			
			}	

        }


		
        $this->context->smarty->assign(array(
            'paramsonechannel_MAIN' => $params,
            'numer_zam' => $nr_zam2,
            'module_dir_OC_MAIN' => $this->module->getPathUri(),
            'form_url_MAIN' => $form_url,
            'DP_TEST_MAIN' => Configuration::get('DP_TEST_OC_MAIN'),
           'form_url_redirect_MAIN' => $this->context->link->getModuleLink('dotpay', 'redirect'),
            'dPorder_summary_MAIN' => Configuration::get('DP_SUMMARY_OC_MAIN'),
            'DP_CHANNELS_VIEW_MAIN' => Configuration::get('DP_CHANNELS_VIEW_MAIN'),
			'DP_CHANNEL_NAME_MAIN' => $this->getChannelInfo(Configuration::get('DP_ONE_CHANNEL_SELECTED_MAIN'),'name'),
			'DP_CHANNEL_IMG_MAIN' => $this->getChannelInfo(Configuration::get('DP_ONE_CHANNEL_SELECTED_MAIN'),'logo'),
			'DP_CHANNEL_NUMBER_MAIN' => Configuration::get('DP_ONE_CHANNEL_SELECTED_MAIN'),
			'DP_AGREEMENT_BYLAW_MAIN' => $this->DotpayAgreement('bylaw'),
			'DP_AGREEMENT_PERSONAL_DATA_MAIN' => $this->DotpayAgreement('personal_data'),
			'DP_CHK_ENABLE_MAIN' => Configuration::get('DP_CHK_OC_MAIN', false),
            ));
        $this->setTemplate($template.".tpl");
    

	
	}
}