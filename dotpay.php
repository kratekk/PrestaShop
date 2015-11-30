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
    	const DOTPAY_PAYMENTS_TEST_CUSTOMER = '';
        const DOTPAY_PAYMENTS_TEST_CUSTOMER_PIN = '';
	
	protected $config_form = false;

	public function __construct()
	{
		$this->name = 'dotpay';
		$this->tab = 'payments_gateways';
		$this->version = '1.5.3';
        $this->author = 'tech@dotpay.pl';
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_); 
		$this->bootstrap = true;
		$this->controllers = array('payment', 'callback');
		$this->is_eu_compatible = 1;
		parent::__construct();

		$this->displayName = $this->l('Dotpay');
		 if (_PS_VERSION_ < 1.6 ) {
            $this->description = $this->l('WARNING! This Dotpay payment module is designed only for PrestaShop 1.6 and newer. For older PrestaShop version use an older version of the Dotpay payment module available to download from following address: https://github.com/dotpay/PrestaShop/tags');
            parent::uninstall();
        } else {
			$this->description = $this->l('Dotpay payment module');
        }

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall Dotpay payment module?');
		
		
	}
	
		function install()
	{
			if (!parent::install() OR !$this->registerHook('payment') OR !$this->registerHook('paymentReturn') OR !$this->registerHook('header') OR !$this->registerHook('backOfficeHeader') OR !$this->registerHook('displayPaymentEU'))
			return false;
		
		Configuration::updateValue('DP_TEST', false);
        Configuration::updateValue('DP_CHK', false);
			if (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] === '443')) { 
				Configuration::updateValue('DP_SSL', true); 
			}else{
				Configuration::updateValue('DP_SSL', false); 
			}
		Configuration::updateValue('DP_SUMMARY', true); 	
        Configuration::updateValue('DP_ID', self::DOTPAY_PAYMENTS_TEST_CUSTOMER);
        Configuration::updateValue('DP_PIN', self::DOTPAY_PAYMENTS_TEST_CUSTOMER_PIN);
        Configuration::updateValue('DOTPAY_CONFIGURATION_OK', false);
		

		if (Validate::isInt(Configuration::get('PAYMENT_DOTPAY_NEW_STATUS')) XOR (Validate::isLoadedObject($order_state_new = new OrderState(Configuration::get('PAYMENT_DOTPAY_NEW_STATUS')))))
		{
			$order_state_new = new OrderState();
			$order_state_new->name[Language::getIdByIso("pl")] = "Oczekuje potwierdzenia platnosci Dotpay";
			$order_state_new->name[Language::getIdByIso("en")] = "Awaiting payment confirmation Dotpay";
			$order_state_new->send_email = false;
			$order_state_new->invoice = false;
			$order_state_new->unremovable = false;
			$order_state_new->color = "#4169E1";
			if (!$order_state_new->add())
				return false;
			if(!Configuration::updateValue('PAYMENT_DOTPAY_NEW_STATUS', $order_state_new->id))
				return false;
		}
	
		return true;
	}
	
		function uninstall()
	{
		return (parent::uninstall());
	}
	
	
	/**
	 * Load the configuration form
	 */
        public function getContent()
        {
			if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on') {$is_https = 0;}else{$is_https = 1;}
			if (version_compare(_PS_VERSION_, "1.6.0.1", ">=")) {$is_compatibility_currency = 1;}else{$is_compatibility_currency = 0;}
            $this->_postProcess();
            $this->context->smarty->assign(array(
                'module_dir' => $this->_path,
                'DOTPAY_CONFIGURATION_OK' => Configuration::get('DOTPAY_CONFIGURATION_OK', false),
                'DP_URLC' => $this->context->link->getModuleLink('dotpay', 'callback', array('ajax' => '1')),
                'DP_URI' => $_SERVER['REQUEST_URI'],
                'SSL_ENABLED' => Configuration::get('PS_SSL_ENABLED'),
				'bad_ID' => $this->l('Incorrect ID (6 digits maximum)'),
				'bad_PIN' => $this->l('Incorrect PIN (minimum 16 and maximum 32 alphanumeric characters)'),
				'forced_HTTPS' => $this->l('(forced YES)'),
				'DOTPAY_HTTPS' => Configuration::get('DP_SSL'),
				'DOTPAY_SUMMARY' => Configuration::get('DP_SUMMARY')
            ));
            $form_values = $this->getConfigFormValues();
            foreach ($form_values as $key => $value)
                $this->context->smarty->assign($key, $value);
				$this->context->smarty->assign("is_https", $is_https);
				$this->context->smarty->assign("is_compatibility_currency", $is_compatibility_currency);
                $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
                return $output.$this->renderForm();
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
		return array(
			'form' => array(
				'legend' => array(
				'title' => $this->l('Settings'),
				'icon' => 'icon-wrench',
				),
				'input' => array(
					array(
						'type' => 'text',
						'name' => 'DP_ID',
						'label' => $this->l('ID'),
						'size' => 6, 
						'class' => 'fixed-width-sm',
						'desc' => $this->l('The same as in Dotpay user panel').' <div id="infoID" /></div>',
						'required' => true						
					),
					array(
						'type' => 'text',
						'name' => 'DP_PIN',
						'label' => $this->l('PIN'),
						'class' => 'fixed-width-lg',
						'desc' => $this->l('The same as in Dotpay user panel').' <div id="infoPIN" /></div>',
						'required' => true
					),
					
					
					array(
						'type' => 'switch',
                        'label' => '<div id="ukryj_test">'.$this->l('Test mode').'</div>',
						'name' => 'DP_TEST',
						'is_bool' => true,
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
						'type' => 'switch',
                        'label' => $this->l('This shop is using HTTPS'),
						'name' => 'DP_SSL',
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
						'name' => 'DP_CHK',
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
                        'label' => $this->l('Direct forwarding to Dotpay'),
						'name' => 'DP_SUMMARY',
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

				),
				'submit' => array(
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
			'DP_TEST' => Configuration::get('DP_TEST', false),
			'DP_CHK'  => Configuration::get('DP_CHK', false),
			'DP_SSL'  => Configuration::get('DP_SSL', false),
			'DP_SUMMARY'  => Configuration::get('DP_SUMMARY', false),
			'DP_ID' => Configuration::get('DP_ID'),
			'DP_PIN' => Configuration::get('DP_PIN'),
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
                $values["DP_SSL"] = Configuration::get('PS_SSL_ENABLED') && Tools::getValue("DP_SSL");                           
                $values["DOTPAY_CONFIGURATION_OK"] = !empty($values["DP_PIN"]) && is_numeric($values["DP_ID"]);
                if ($values["DOTPAY_CONFIGURATION_OK"] && Tools::strlen($values["DP_ID"]) < 6) $values["DP_TEST"] = false;
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
        $this->smarty->assign(array('module_dir' => $this->_path));
        if ($this->active && Configuration::get('DOTPAY_CONFIGURATION_OK'))
            return $this->display(__FILE__, 'payment.tpl');
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
            'params_dotpay_payment' => $param,
            'module_dir' => $this->getPathUri(),
            'form_url' => $form_url,
        ));
        return $this->display(__FILE__, 'payment_return.tpl');            
    }
	
	
	/**
	* add 'Advanced EU Compliance' (2015.11.27)
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
	
	
  
  static public function check_urlc_legacy() 
            {
		$signature =
			Configuration::get('DP_PIN').":".
			Configuration::get('DP_ID').":".
			Tools::getValue('control').":".
			Tools::getValue('t_id').":".
			Tools::getValue('amount').":". 
			Tools::getValue('email').":".
			Tools::getValue('service').":".  
			Tools::getValue('code').":".
			Tools::getValue('username').":".
			Tools::getValue('password').":".
			Tools::getValue('t_status');
	$signature=hash('md5', $signature);
	return (Tools::getValue('md5') == $signature);
    } 
    
}
