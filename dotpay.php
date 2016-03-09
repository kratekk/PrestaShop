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

if (!defined('_PS_VERSION_'))
	exit;

class dotpay extends PaymentModule
{
    	const DOTPAY_PAYMENTS_TEST_CUSTOMER_OC_MAIN = '';
        const DOTPAY_PAYMENTS_TEST_CUSTOMER_PIN_OC_MAIN = '';
        const DP_CHANNELS_VIEW_MAIN = 1;  //default select payment channels on the shop site
	
	
	protected $config_form = false;

	public function __construct()
	{
		$this->name = 'dotpay';
		$this->tab = 'payments_gateways';
		$this->version = '1.6.0';
        $this->author = 'tech@dotpay.pl';
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_); 
		$this->bootstrap = true;
		$this->controllers = array('payment', 'callback', 'redirect');
		$this->is_eu_compatible = 1;
		parent::__construct();

		$this->displayName = $this->l('Dotpay Payment Module');
		 if (_PS_VERSION_ < 1.6 ) {
            $this->description = $this->l('WARNING! This Dotpay payment module is designed only for the PrestaShop 1.6 and later. For older version PrestaShop use an older version of the Dotpay payment module  available to download from the following address: https://github.com/dotpay/PrestaShop/tags');
            parent::uninstall();
        } else {
			$this->description = $this->l('Fast and secure internet payments');
        }

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall Dotpay payment module?');	
	}
	
		function install()
	{
			if (!parent::install() OR !$this->registerHook('payment') OR !$this->registerHook('paymentReturn') OR !$this->registerHook('header') OR !$this->registerHook('backOfficeHeader') OR !$this->registerHook('displayPaymentEU') OR !$this->createAmountTable())
			return false;
		
		Configuration::updateValue('DP_TEST_OC_MAIN', false);
		Configuration::updateValue('DP_REFERENCE_MAIN', true);
        Configuration::updateValue('DP_CHK_OC_MAIN', true);
			if (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] === '443')) { 
				Configuration::updateValue('DP_SSL_OC_MAIN', true); 
			}else{
				Configuration::updateValue('DP_SSL_OC_MAIN', false); 
			}
		Configuration::updateValue('DP_SUMMARY_OC_MAIN', true); 	
        Configuration::updateValue('DP_ID_OC_MAIN', self::DOTPAY_PAYMENTS_TEST_CUSTOMER_OC_MAIN);
        Configuration::updateValue('DP_CHANNELS_VIEW_MAIN', self::DP_CHANNELS_VIEW_MAIN);
        Configuration::updateValue('DP_PIN_OC_MAIN', self::DOTPAY_PAYMENTS_TEST_CUSTOMER_PIN_OC_MAIN);
        Configuration::updateValue('DOTPAY_CONFIGURATION_OK_OC_MAIN', false);
		

		if (Validate::isInt(Configuration::get('PAYMENT_DOTPAY_NEW_STATUS')) XOR (Validate::isLoadedObject($order_state_new = new OrderState(Configuration::get('PAYMENT_DOTPAY_NEW_STATUS')))))
		{
			$order_state_new = new OrderState();
			$order_state_new->name[Language::getIdByIso("en")] = "Awaiting payment confirmation Dotpay";
			$order_state_new->name[Language::getIdByIso("pl")] = "Oczekuje potwierdzenia platnosci Dotpay";
			$order_state_new->send_email = false;
			$order_state_new->invoice = false;
			$order_state_new->unremovable = false;
			$order_state_new->color = "#4169E1";
			if (!$order_state_new->add())
				return false;
			if(!Configuration::updateValue('PAYMENT_DOTPAY_NEW_STATUS', $order_state_new->id))
				return false;
		}
		
		
		
	     if (Validate::isInt(Configuration::get('PAYMENT_DOTPAY_NEW_STATUS_UNPAID')) XOR (Validate::isLoadedObject($order_state_new1 = new OrderState(Configuration::get('PAYMENT_DOTPAY_NEW_STATUS_UNPAID')))))
		{
			$order_state_new1 = new OrderState();
			$order_state_new1->name[Language::getIdByIso("en")] = "Chosen Dotpay payments - awaiting payment";
			$order_state_new1->name[Language::getIdByIso("pl")] = "Wybrano płatność z Dotpay - oczekuje na wpłatę";		
			$order_state_new1->send_email = false;
			$order_state_new1->invoice = false;
			$order_state_new1->unremovable = false;
			$order_state_new1->color = "#74c4ff";
			if (!$order_state_new1->add())
				return false;
			if(!Configuration::updateValue('PAYMENT_DOTPAY_NEW_STATUS_UNPAID', $order_state_new1->id))
				return false;
		}
		
	
		return true;
	
	
	
	}
	
	    /**
     * add new table: currency and amount orders (to verify callback)
     */
    private function createAmountTable() {

		$query = ' 
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'dotpay_amount_MAIN` (
				  `i_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  `i_id_order` int(10) unsigned NOT NULL,
				  `i_amount` varchar(16) NOT NULL,
				  `reference` varchar(9) DEFAULT NULL,
				  `i_currency` varchar(3) NOT NULL,
				  `cookie_checksum` varchar(250) NOT NULL,
				  `i_id_customer` int(10) unsigned DEFAULT NULL,
				  `i_id_connections` int(10) unsigned DEFAULT NULL,
				  `i_suma` varchar(32) NOT NULL,
				  `i_is_guest` int(1) NOT NULL,
				  `i_id_guest` int(10) NOT NULL,
				  `i_secure_key` varchar(64) NOT NULL,
				  `i_datetime_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
				) CHARSET=utf8
				';
		
				
		
		$query1 ='ALTER TABLE `'._DB_PREFIX_.'dotpay_amount_MAIN` ADD UNIQUE INDEX (`i_suma`)';
	
			 Db::getInstance()->execute($query);
			 Db::getInstance()->execute($query1);
	
		  return true;
    }
	
	
	
	
	
		function uninstall()
			{
				$sql_del = 'DROP TABLE `'._DB_PREFIX_.'dotpay_channel_MAIN`';
				Db::getInstance()->execute($sql_del);
				 
				return (parent::uninstall());
			}
	
	
	/**
	 * Load the configuration form
	 */
        public function getContent()
        {
			$is_https = '';
			
			if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on') {$is_https_MAIN = 0;}else{$is_https_MAIN = 1;}
			if (version_compare(_PS_VERSION_, "1.6.0.1", ">=")) {$is_compatibility_currency_MAIN = 1;}else{$is_compatibility_currency_MAIN = 0;}
            $this->_postProcess();
            $this->context->smarty->assign(array(
                'module_dir_OC_MAIN' => $this->_path,
                'DOTPAY_CONFIGURATION_OK_OC_MAIN' => Configuration::get('DOTPAY_CONFIGURATION_OK_OC_MAIN', false),
                'DP_URLC_OC_MAIN' => $this->context->link->getModuleLink('dotpay', 'callback', array('ajax' => '1')),
                'DP_URI_OC_MAIN' => $_SERVER['REQUEST_URI'],
                'SSL_ENABLED_OC_MAIN' => Configuration::get('PS_SSL_ENABLED'),
			//	'DP_ONE_CHANNEL_SELECTED_MAIN' => Configuration::get('DP_ONE_CHANNEL_SELECTED_MAIN'),
			//	'DP_ONE_CHANNEL_IMG_MAIN' => Configuration::get('DP_ONE_CHANNEL_IMG_MAIN'),
			//	'DP_ONE_CHANNEL_NAME_MAIN' => Configuration::get('DP_ONE_CHANNEL_NAME_MAIN'),
				'bad_ID_OC_MAIN' => $this->l('Incorrect ID (6 digits maximum)'),
				'bad_PIN_OC_MAIN' => $this->l('Incorrect PIN (minimum 16 and maximum 32 alphanumeric characters)'),
				'forced_HTTPS_OC_MAIN' => $this->l('(forced YES)'),
				'DOTPAY_HTTPS_OC_MAIN' => Configuration::get('DP_SSL_OC_MAIN'),
				'DP_REFERENCE_MAIN' => Configuration::get('DP_REFERENCE_MAIN'),
				'DOTPAY_SUMMARY_OC_MAIN' => Configuration::get('DP_SUMMARY_OC_MAIN')
            ));
            $form_values = $this->getConfigFormValues();
            foreach ($form_values as $key => $value)
                $this->context->smarty->assign($key, $value);
				$this->context->smarty->assign("is_https_MAIN", $is_https_MAIN);
				$this->context->smarty->assign("is_compatibility_currency_MAIN", $is_compatibility_currency_MAIN);
            if (version_compare(_PS_VERSION_, "1.6.0.1", ">=")) {
                $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
                return $output.$this->renderForm();
            } else 
                return $this->display(__FILE__, 'views/templates/admin/content.tpl');
        } 

	/**
	 * Create the form that will be displayed in the configuration of your module.
	 */
	protected function renderForm()
	{
		$helper = new HelperForm();

		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$helper->module = $this;
		$helper->default_form_language = $this->context->language->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitDotpayModule';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
			.'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');

		$helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($this->getConfigForm()));
	}
	

      
	
	/**
	 * Create the structure of your form.
	 */

protected function getConfigForm()
	{
    
        $optionsW = array(
              array(
                'id_option2' => 1, 
                'name2' => $this->l('Channel list on this Shop (tiles with logos) ->') 
              ),
              array(
                'id_option2' => 2,
                'name2' => $this->l('Channel list on this Shop (drop-down list)  ->')
              ),
			 array(
                'id_option2' => 3,
                'name2' => $this->l('Channel list on the Dotpay page  ->')
              ),
        );
		
		
    
								if (Configuration::get('DOTPAY_CONFIGURATION_OK_OC_MAIN') && strlen(Configuration::get('DP_ID_OC_MAIN')) > 5){
									$select_desc = '<div id="ukryj_test_ch_desc">';
									
											$select_desc .= '<strong style="color: red;">'.$this->l(' For this ID Account: "').Configuration::get('DP_ID_OC_MAIN').$this->l('" lack of channel list !').'</strong>';
											
									$select_desc .= '</div>';
										
									}else{
										$select_desc = '<div id="ukryj_test_ch_desc"><strong style="color: red;">'.$this->l(' You must first save the ID and save the changes before selecting channel. ').'</strong></div>';
									}
	
		return array(
			'form' => array(
				'legend' => array(
				'title' => $this->l('Settings'),
				'icon' => 'icon-wrench',
				),
				'input' => array(
					array(
						'type' => 'text',
						'name' => 'DP_ID_OC_MAIN',
						'label' => $this->l('ID'),
						'size' => 6, 
						'class' => 'fixed-width-sm',
						'desc' => $this->l('The same as in Dotpay user panel').' <div id="infoID" /></div>',
						'required' => true						
					),
					array(
						'type' => 'text',
						'name' => 'DP_PIN_OC_MAIN',
						'label' => $this->l('PIN'),
						'class' => 'fixed-width-lg',
						'desc' => $this->l('The same as in Dotpay user panel').' <div id="infoPIN" /></div>',
						'required' => true
					),
					
					array(
						'type' => 'switch',
                        'label' => '<div id="ukryj_test">'.$this->l('Test mode').'</div>',
						'name' => 'DP_TEST_OC_MAIN',
						'is_bool' => true,
						'required' => true, 
						'desc' => '<div id="ukryj_test_desc">'.$this->l('I\'m using Dotpay test account (test ID)').'</div>',
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => true,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => false,
								'label' => $this->l('Disabled')
							)
						),
					),
     
                    
					 array(
                        'type' => 'select',
						'class' => 'fixed-width-xxl',
                        'label' => '<span id="ukryj_test_widg" class="label-tooltip" data-toggle="tooltip" title="'.$this->l('Display a list of payments channels on this shop will shorten the path payment for the customer. You can also choose only one specific payment channel to limit available payment methods (in next step it will be necessary to set a specific payment channel from available list)').'">'.$this->l('Presentation of payment channels'),
                        'name' => 'DP_CHANNELS_VIEW_MAIN',
                        'desc' => $this->l('Possibility to display payment channel in the shop or only one selected channel (selection in the next step).'),
                        'required' => true, 
                        'options' => array(
                                          'query' => $optionsW,
                                          'id' => 'id_option2', 
                                          'name' => 'name2'
                                        )            

                      ),

                     array(
						'type' => 'switch',
                        'label' => $this->l('This shop is using HTTPS'),
						'name' => 'DP_SSL_OC_MAIN',
                        'desc' => $this->l('Use secure HTTPS protocol for communication with Dotpay').' <span id="https_replace"></span>',
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => true,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => false,
								'label' => $this->l('Disabled')
							)
						),
					),                                                                        
                     array(
						'type' => 'switch',
                        'label' => $this->l('CHK mode'),
						'name' => 'DP_CHK_OC_MAIN',
                        'desc' => $this->l('Secure payment parameters'),
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => true,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => false,
								'label' => $this->l('Disabled')
							)
						),
					),
					array(
						'type' => 'switch',
						'label' => '<span id="ukryj_summary_multi">'.$this->l('Direct forwarding to Dotpay').'</span>',
						'name' => 'DP_SUMMARY_OC_MAIN',
                        'desc' => $this->l('Without displaying an additional summary'),
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => true,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => false,
								'label' => $this->l('Disabled')
							)
						),
					),
					
					array(
						'type' => 'switch',
						'label' => '<span class="label-tooltip" data-toggle="tooltip" title="'.$this->l('This number will help you find a transaction in your shop. The buyer can also see this number.').'">'.$this->l('Add new reference to description').'</span>',
						'name' => 'DP_REFERENCE_MAIN',
                        'desc' => $this->l('Create a new order before receiving confirmation from the Dotpay. Data from the Shopping Cart will be deleted.'),
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => true,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => false,
								'label' => $this->l('Disabled')
							)
						),
					), 
					

				),
				'submit' => array(
					
					'class' => 'center-block',
					'title' => $this->l('Save'),
				),
			),
		);
	}

	 
	/**
	 * Set values for the inputs.
	 */
	protected function getConfigFormValues()
	{
		return array(
			'DP_TEST_OC_MAIN' => Configuration::get('DP_TEST_OC_MAIN', false),
			'DP_CHK_OC_MAIN'  => Configuration::get('DP_CHK_OC_MAIN', false),
			'DP_SSL_OC_MAIN'  => Configuration::get('DP_SSL_OC_MAIN', false),
			'DP_SUMMARY_OC_MAIN'  => Configuration::get('DP_SUMMARY_OC_MAIN', false),
		//	'DP_ONE_CHANNEL_SELECTED_MAIN'  => Configuration::get('DP_ONE_CHANNEL_SELECTED_MAIN', false),
		//	'DP_ONE_CHANNEL_IMG_MAIN'  => $this->channelsListAPI(Configuration::get('DP_ONE_CHANNEL_SELECTED_MAIN'),'logo'),
		//	'DP_ONE_CHANNEL_NAME_MAIN'  => $this->channelsListAPI(Configuration::get('DP_ONE_CHANNEL_SELECTED_MAIN'),'name'),
			'DP_ID_OC_MAIN' => Configuration::get('DP_ID_OC_MAIN'),
			'DP_CHANNELS_VIEW_MAIN' => Configuration::get('DP_CHANNELS_VIEW_MAIN'),
			'DP_REFERENCE_MAIN' => Configuration::get('DP_REFERENCE_MAIN'),
			'DP_PIN_OC_MAIN' => Configuration::get('DP_PIN_OC_MAIN'),
			'DP_THISMODULE_VERSION_MAIN' => $this->version,
			
		);
	}

	/**
	 * Save form data.
	 */
	protected function _postProcess()
	{
            if (Tools::isSubmit('submitDotpayModule')) 
            {
		
                $values = $this->getConfigFormValues();
                foreach (array_keys($values) as $key)
                    $values[$key] = trim(Tools::getValue($key));
                $values["DP_SSL_OC_MAIN"] = Configuration::get('PS_SSL_ENABLED') && Tools::getValue("DP_SSL_OC_MAIN");                           
                $values["DOTPAY_CONFIGURATION_OK_OC_MAIN"] = ($values["DP_PIN_OC_MAIN"]) && is_numeric($values["DP_ID_OC_MAIN"]);
                if ($values["DOTPAY_CONFIGURATION_OK_OC_MAIN"] && Tools::strlen($values["DP_ID_OC_MAIN"]) < 6) $values["DP_TEST_OC_MAIN"] = false;
                foreach ($values as $key => $value)
                    Configuration::updateValue($key, $value);
            }
        }
	/**
	* Add the CSS & JavaScript files you want to be loaded in the BO.
	*/
	public function hookBackOfficeHeader()
	{
		$this->context->controller->addCSS($this->_path.'css/back.css');                   
	}
	/**
	 * Add the CSS & JavaScript files you want to be added on the FO.
	 */

	public function hookHeader()
	{
		$this->context->controller->addCSS($this->_path.'css/front.css');
	}
	

	 public function hookPayment()
			{
        $this->smarty->assign(array('module_dir_OC_MAIN' => $this->_path));
        if ($this->active && Configuration::get('DOTPAY_CONFIGURATION_OK_OC_MAIN') ){
		//	$this->context->smarty->assign("DP_ONE_CHANNEL_SELECTED_MAIN", Configuration::get('DP_ONE_CHANNEL_SELECTED_MAIN'));
			$this->context->smarty->assign("DP_CHANNELS_VIEW_MAIN", Configuration::get('DP_CHANNELS_VIEW_MAIN'));
			$this->context->smarty->assign("DP_REFERENCE_MAIN", Configuration::get('DP_REFERENCE_MAIN'));
		//	$this->context->smarty->assign("DP_ONE_CHANNEL_IMG_MAIN", $this->channelsListAPI(Configuration::get('DP_ONE_CHANNEL_SELECTED_MAIN'),'logo') );
		//	$this->context->smarty->assign("DP_ONE_CHANNEL_NAME_MAIN", $this->channelsListAPI(Configuration::get('DP_ONE_CHANNEL_SELECTED_MAIN'),'name'));
            return $this->display(__FILE__, 'payment.tpl');
			}
		}
	

    public function hookPaymentReturn($params)
    {
        if (!$this->active)
            return;
        
        $customer = new Customer($params['objOrder']->id_customer);
        if (!Validate::isLoadedObject($customer))
            return;
        
       
        if ((bool)Context::getContext()->customer->is_guest) $form_url=$this->context->link->getPageLink('guest-tracking', true);
        else $form_url=$this->context->link->getPageLink('history', true);
   
        $param = array(
            'order_reference' => $params['objOrder']->reference,
            'email' => $customer->email,
            'submitGuestTracking' => 1       
        );
        $this->smarty->assign(array(
            'params_dotpay_payment_MAIN' => $param,
            'module_dir_OC_MAIN' => $this->getPathUri(),
            'form_url_MAIN' => $form_url,
        ));
        return $this->display(__FILE__, 'payment_return.tpl');            
    }
	
	
	/**
	* add 'Advanced EU Compliance'
	*/
	
	public function hookDisplayPaymentEU($params)
		{
			if (!$this->active)
				return;

			if (!$this->checkCurrency($params['cart']))
				return;
				
					$payment_options = array(
						'cta_text' => $this->l('Fast and secure internet payments'),
						'logo' => $this->_path.'img/dotpay_logo85.png',
						'action' => $this->context->link->getModuleLink($this->name, 'payment', array(), true)
					);

			return $payment_options;
		}

	public function checkCurrency($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}
	//.
	
		/**
	 * Calculate sygnature based on dotpay parameters and pin for api_version = 'dev'
	 *
	 */

	static public function check_urlc_dev()
	{
					
					$signature = Configuration::get('DP_PIN_OC_MAIN').Configuration::get('DP_ID_OC_MAIN').
					Tools::getValue('operation_number').
					Tools::getValue('operation_type').
					Tools::getValue('operation_status').
					Tools::getValue('operation_amount').
					Tools::getValue('operation_currency').
					Tools::getValue('operation_withdrawal_amount').
					Tools::getValue('operation_commission_amount').
					Tools::getValue('operation_original_amount').
					Tools::getValue('operation_original_currency').
					Tools::getValue('operation_datetime').
					Tools::getValue('operation_related_number').
					Tools::getValue('control').
					Tools::getValue('description').
					Tools::getValue('email').
					Tools::getValue('p_info').
					Tools::getValue('p_email').
					Tools::getValue('channel').
					Tools::getValue('channel_country').
					Tools::getValue('geoip_country');	
					
					
			$signature = hash('sha256', $signature);
			return (Tools::getValue('signature') === $signature);
	}



    
}
