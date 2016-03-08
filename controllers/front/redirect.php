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


### recalculate new check sum 'CHK'

 class dotpayredirectModuleFrontController extends ModuleFrontController
	{ 

	//public function initContent()
	public function initContent()
	{
		$this->display_column_left = false;
		//parent::initContent();
		parent::initContent();

		$cart = $this->context->cart;
		$smarty = $this->context->smarty;
		$cookie = $this->context->cookie;
		

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
                Tools::redirect('index.php?controller=order&step=1');
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer))
                Tools::redirect('index.php?controller=order&step=1');
		
			if(!$_POST['channel'] || (int)$_POST['channel'] == '')	
				//Tools::redirect($this->context->link->getModuleLink('dotpay', 'payment'));
				Tools::redirect('index.php?controller=order&step=3');
			
			
		// get parameters
					$params2 = array(
									'channel' => (int)$_POST['channel'],
									'ch_lock' => 1,
									'bylaw' => (int)$_POST['bylaw'],
									'personal_data' => (int)$_POST['personal_data'],
									'id' => (int)$_POST['id'],
									'amount' => (float)$_POST['amount'],
									'currency' => $_POST['currency'],
									'description' => trim($_POST['description']),
									'lang' => trim($_POST['lang']),
									'ch_lock' => (int)$_POST['ch_lock'],
									'URL' => $_POST['URL'],
									'type' => (int)$_POST['type'],						
									'buttontext' => $_POST['buttontext'],                        
									'URLC' => $_POST['URLC'],
									'control' => $_POST['control'],
									'firstname' => trim($_POST['firstname']),
									'lastname' => trim($_POST['lastname']),
									'email' => trim($_POST['email']),
									'street' => trim($_POST['street']),
									'street_n1' => trim($_POST['street_n1']),
									'street_n2' => trim($_POST['street_n2']),
									'state' => trim($_POST['state']),
									'addr3' => trim($_POST['addr3']),
									'city' => trim($_POST['city']),
									'postcode' => trim($_POST['postcode']),
									'phone' => trim($_POST['phone']),
									'country' => trim($_POST['country']),
									'api_version' => $_POST['api_version'],

					); 		
					
				// calculate check sum
							$chk2 = Configuration::get('DP_PIN_OC_MAIN').
									$params2["api_version"].
									$params2["lang"].
									$params2["id"].
									$params2["amount"].
									$params2["currency"].
									$params2["description"].
									$params2["control"].
									$params2["channel"].
									$params2["ch_lock"].
									$params2["URL"].
									$params2["type"].
									$params2["buttontext"].
									$params2["URLC"].
									$params2["firstname"].
									$params2["lastname"].
									$params2["email"].
									$params2["street"].
									$params2["street_n1"].
									$params2["street_n2"].
									$params2["state"].
									$params2["addr3"].
									$params2["city"].
									$params2["postcode"].
									$params2["phone"].
									$params2["country"].
									$params2["bylaw"].
									$params2["personal_data"]
								;		
				
						if(Configuration::get('DP_CHK_OC_MAIN') && (Configuration::get('DP_CHANNELS_VIEW_MAIN') == 1 || Configuration::get('DP_CHANNELS_VIEW_MAIN') == 2))
									$params2['chk'] = hash('sha256', $chk2);
			

		
		$smarty->assign(array(
            'params_r_MAIN' => $params2,
			'form_url_MAIN' => $_POST['URLDOTPAY'],
            ));

  $this->setTemplate("redirect.tpl");
		
	}	

}

?>
