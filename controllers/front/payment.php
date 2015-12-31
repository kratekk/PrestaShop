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
            $form_url = $this->context->link->getModuleLink('dotpay', 'payment', array('control' => $cart->id, 'status' => 'OK'), Configuration::get('DP_SSL', false));
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
				if (Configuration::get('DP_TEST') == 1 && (strlen(Configuration::get('DP_ID'))) > 5) $form_url1="test_payment/";
				if (strlen(Configuration::get('DP_ID')) > 5 && Configuration::get('DP_TEST') != 1) $form_url1="t2/";
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
				
			Db::getInstance()->Execute('INSERT INTO '._DB_PREFIX_.'dotpay_amount (i_id_order,i_amount,i_currency,cookie_checksum,i_id_customer,i_suma,i_is_guest,i_id_guest,i_id_connections,i_secure_key) '.'VALUES("'.$cart->id.'","'.$i_amount.'","'.$currency["iso_code"].'","'.$cookie_checksum.'","'.$i_id_customer.'","'.$sum_order_cust.'","'.$cookie_is_guest.'","'.$cookie_id_guest.'","'.$cookie_id_connections.'","'.$customer->secure_key.'")');
			
			
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
				
				
				
				
			if ((strlen(Configuration::get('DP_ID'))) > 5) {				      
				
				
				$params = array(
                    'id' => Configuration::get('DP_ID'),
                    'amount' => (float)$cart->getOrderTotal(true, Cart::BOTH),
                    'currency' => $currency["iso_code"],
                    'description' => Configuration::get('PS_SHOP_NAME').$lang_desc.$cart->id.$firma.$inne,
                    'lang' => $language,
                    'channel' => '',
                    'ch_lock' => '',
                    'URL' => $this->context->link->getModuleLink('dotpay', 'payment', array('control' => $cart->id), Configuration::get('DP_SSL', false)),                        
                    'type' => 0,                        
                    'buttontext' => '',                        
                    'URLC' => $this->context->link->getModuleLink('dotpay', 'callback', array('ajax' => '1'), Configuration::get('DP_SSL', false)),
                    'control' => $cart->id.'|'.$sum_order_cust,
                    'firstname' => $customer->firstname,
                    'lastname' => $customer->lastname,                        
                    'email' => $customer->email,
                    'street' => $address->address1,
                    'street_n1' => $address->address2,
                    'street_n2' => '',
                    'state' => '',
                    'addr3' => '',
                    'city' => $address->city,
                    'postcode'=> $address->postcode,
                    'phone'=> $tel_nr,
                    'country'=> $iso_lang->iso_code,
                    'api_version' => 'dev'
					);
			


		$chk = Configuration::get('DP_PIN').
				$params["api_version"].
				$params["lang"].
				$params["id"].
				$params["amount"].
				$params["currency"].
				$params["description"].
				$params["control"].
				$params["channel"].
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
				$params["country"];
				
			if(Configuration::get('DP_CHK'))
				$params['chk']=hash('sha256', $chk);
		
			
			
			}else{ 

				  $params = array(
                    'id' => Configuration::get('DP_ID'),
                    'amount' => (float)$cart->getOrderTotal(true, Cart::BOTH),
                    'currency' => $currency["iso_code"],
					'description' => Configuration::get('PS_SHOP_NAME').$lang_desc.$cart->id.$firma.$inne,
                    'url' => $this->context->link->getModuleLink('dotpay', 'payment', array('control' => $cart->id), Configuration::get('DP_SSL', false)),
					'lang' => $language,	
                    'type' => 0,                        
                    'urlc' => $this->context->link->getModuleLink('dotpay', 'callback', array('ajax' => '1'), Configuration::get('DP_SSL', false)),
                    'control' => $cart->id.'|'.$sum_order_cust,
                    'forename' => $customer->firstname,
                    'surname' => $customer->lastname,                        
                    'email' => $customer->email,
                    'street' => $address->address1,
					'street_n1' => $address->address2,
                    'city' => $address->city,
					'phone'=> $tel_nr,
                    'postcode'=> $address->postcode,
					'country'=> $iso_lang->iso_code,
                    'api_version' => 'legacy'
					);
				$chk = $params['id'].$params['amount'].$params['currency'].$params['description'].$params['control'].Configuration::get('DP_PIN');
				 $chk = rawurlencode($chk);
					if(Configuration::get('DP_CHK'))
						$params['chk']=hash('md5', $chk);
			
			}

			

        }
        $this->context->smarty->assign(array(
            'params' => $params,
            'numer_zam' => $cart->id,
            'module_dir' => $this->module->getPathUri(),
            'form_url' => $form_url,
            'dPorder_summary' => Configuration::get('DP_SUMMARY'),
            ));
        $this->setTemplate($template.".tpl");
    }
}