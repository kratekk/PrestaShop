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

require_once(__DIR__.'/DotpayFormHelper.php');
require_once(__DIR__.'/models/Config.php');
require_once(__DIR__.'/api/dev.php');
require_once(__DIR__.'/api/legacy.php');
require_once(__DIR__.'/controllers/front/payment.php');
require_once(__DIR__.'/models/Instruction.php');
require_once(__DIR__.'/models/CreditCard.php');
require_once(__DIR__.'/classes/SellerApi.php');
require_once(__DIR__.'/classes/GithubApi.php');

if (!defined('_PS_VERSION_'))
	exit;

/**
 * Function adds Dotpay Form Helper to Smarty tags
 * @param array $params
 * @param type $smarty
 * @return string
 */
function dotpayGenerateForm(Array $params, $smarty)
{
    $data = (isset($params['form']))?$params['form']:array();
    return DotpayFormHelper::generate($data);
}

/**
 * Dotpay payment module
 */
class dotpay extends PaymentModule {
    
    /**
     *
     * @var DotpayConfig 
     */
    private $config;
    
    /**
     *
     * @var DotpayApi 
     */
    private $api;
    
    /**
     * Initialize module
     */
    public function __construct() {
        $this->name = 'dotpay';
        $this->tab = 'payments_gateways';
        $this->version = '2.0.2';
        $this->author = 'tech@dotpay.pl';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_); 
        $this->bootstrap = true;
        $this->controllers = array('payment', 'preparing', 'callback', 'back', 'status', 'confirm', 'ocmanage', 'ocremove');
        $this->is_eu_compatible = 1;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        parent::__construct();

        $this->displayName = $this->l('Dotpay');
        if (_PS_VERSION_ < 1.6 ) {
            $this->description = $this->l('WARNING! This Dotpay payment module is designed only for the PrestaShop 1.6 and later. For older version PrestaShop use an older version of the Dotpay payment module  available to download from the following address: https://github.com/dotpay/PrestaShop/tags');
            parent::uninstall();
        } else {
            $this->description = $this->l('Fast and secure internet payments');
        }

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Dotpay payment module?');
        
        $this->config = new DotpayConfig();
        
        if($this->config->getDotpayApiVersion()=='legacy') {
            $this->api = new DotpayLegacyApi();
        } else {
            $this->api = new DotpayDevApi();
        }
    }
    
    /**
     * Return relative module path
     * @return string
     */
    public function getPath() {
        return $this->_path;
    }
    
    /**
     * Installing module
     * @return bool
     */
    public function install() {
        if (!parent::install() OR
            !$this->update())
                return false;

        return true;
    }
    
    /**
     * Updating module
     * @return bool
     */
    public function update() {
        if(!$this->setDefaultConfig() OR
            !$this->registerHook('payment') OR
            !$this->registerHook('paymentReturn') OR
            !$this->registerHook('header') OR
            !$this->registerHook('backOfficeHeader') OR
            !$this->registerHook('displayPaymentEU') OR
            !$this->registerHook('displayOrderDetail') OR
            !$this->registerHook('displayCustomerAccount') OR
            !$this->registerHook('displayShoppingCart') OR
            !$this->addDotpayNewStatus() OR
            !$this->addDotpayVirtualProduct() OR
            !DotpayInstruction::create() OR
            !DotpayCreditCard::create() OR
            !$this->addDotpayDiscount())
                return false;
        return true;
    }
    
    /**
     * Uninstalling module
     * @return bool
     */
    public function uninstall() {
        return DotpayCreditCard::drop() && parent::uninstall();
    }
    
    /**
     * Returns HTML for module settings
     * @return string
     */
    public function getContent() {
        $this->saveConfiguration();
        $sellerApi = new DotpaySellerApi($this->config->getDotpaySellerApiUrl());
        
        $version = DotpayGithubApi::getLatestVersion();
        $this->context->smarty->assign(array(
            'regMessEn' => $this->config->isDotpayTestMode() || !$this->config->isAccountConfigOk(),
            'targetForUrlc' => $this->context->link->getModuleLink('dotpay', 'callback', array('ajax' => '1'), $this->isSSLEnabled()),
            'moduleMainDir' => $this->_path,
            'testMode' => $this->config->isDotpayTestMode(),
            'oldVersion' => !version_compare(_PS_VERSION_, "1.6.0.1", ">="),
            'badPhpVersion' => !version_compare(PHP_VERSION, "5.4", ">="),
            'confOK' => $this->config->isAccountConfigOk() && $this->config->isDotpayEnabled(),
            'moduleVersion' => $this->version,
            'apiVersion' => $this->config->getDotpayApiVersion(),
            'phpVersion' => PHP_VERSION,
            'minorPhpVersion' => '5.4',
            'badNewIdMessage' => $this->l('Incorrect ID (6 digits maximum)'),
            'badOldIdMessage' => $this->l('Incorrect ID (5 digits maximum)'),
            'badNewPinMessage' => $this->l('Incorrect PIN (minimum 16 and maximum 32 alphanumeric characters)'),
            'badOldPinMessage' => $this->l('Incorrect PIN (0 or 16 alphanumeric characters)'),
            'valueLowerThanZero' => $this->l('The value must be greater than zero.'),
            'testApiAccount' => !$sellerApi->isAccountRight($this->config->getDotpayApiUsername(), $this->config->getDotpayApiPassword(), $this->config->getDotpayApiVersion()),
            'urlWithNewVersion' => $version['url'],
            'obsoletePlugin' => version_compare($version['version'], $this->version, '>'),
            'canNotCheckPlugin' => $version['version'] === NULL
        ));
        $template = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        return $template.$this->renderForm();
    }
    
    /**
     * Hook for header section in admin area
     */
    public function hookBackOfficeHeader() {
        $this->context->controller->addCSS($this->_path.'web/css/back.css');
    }
    
    /**
     * Hook for header section in front area
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path.'web/css/front.css');
        $this->context->controller->addJS($this->_path.'web/js/noConflict.js');
    }
    
    /**
     * Hook for payment gateways list in checkout site
     * @return string
     */
    public function hookPayment() {
        if(!$this->config->isDotpayEnabled())
            return;
        
        if ($this->active && $this->config->isAccountConfigOk() ) {
            $this->smarty->assign($this->getSmartyVars());
            return $this->display(__FILE__, 'payment.tpl');
        }
    }
    
    public function hookDisplayShoppingCart($params) {
        if(!$this->config->getDotpayExCh() || $this->config->getDotpayExchVPid()===NULL)
            return;
        foreach ($params['products'] as $product) {
            if($product["id_product"] == $this->config->getDotpayExchVPid()) {
                $this->context->cart->deleteProduct($product["id_product"]);
                header('Location: '.$this->getUrl());
                die();
            }
        }
        return;
    }
    
    /**
     * Hook for payment gateways list in checkout site
     * @return string
     */
    public function hookDisplayCustomerAccount() {
        $this->smarty->assign(array(
            'actionUrl' => $this->context->link->getModuleLink('dotpay', 'ocmanage'),
        ));
        return $this->display(__FILE__, 'ocbutton.tpl');
    }
    
    /**
     * Hook for Advanced EU Compliance plugin
     * @param array $params
     * @return string
     */
    public function hookDisplayPaymentEU($params) {
        if (!$this->active)
            return;

        if (!$this->checkCurrency($params['cart']))
            return;
        
        $payment_options = array(
                'cta_text' => $this->l('Fast and secure internet payments'),
                'logo' => $this->_path.'web/img/dotpay_logo85.png',
                'action' => $this->context->link->getModuleLink($this->name, 'payment', array(), true)
        );

        return $payment_options;
    }
    
    public function hookDisplayOrderDetail($params) {
        if(!$this->config->isDotpayRenewEn())
            return '';
        $order = new Order(Tools::getValue('id_order'));
        $instruction = DotpayInstruction::getByOrderId($order->id);
        $context =  Context::getContext();
        $context->cookie->dotpay_channel = $instruction->channel;
        if ($order->module=='dotpay') {
            $this->smarty->assign(array(
                'isRenew' => $order->current_state == $this->config->getDotpayNewStatusId(),
                'paymentUrl' => $this->context->link->getModuleLink('dotpay', 'payment', array('order_id'=>$order->id)),
                'isInstruction' => ($instruction->id!=NULL),
                'instructionUrl' => $this->context->link->getModuleLink('dotpay', 'confirm', array('order_id'=>$order->id)),
            ));
            return $this->display(__FILE__, 'renew.tpl');
        }
    }
    
    /**
     * Register Dotpay Form Helper in Smarty engine
     */
    public function registerFormHelper() {
        $this->context->smarty->unregisterPlugin("function","dotpayGenerateForm");
        $this->context->smarty->registerPlugin("function","dotpayGenerateForm", "dotpayGenerateForm");
    }
    
    /**
     * Check if currency in cart is correct
     * @param \Cart $cart
     * @return boolean
     */
    private function checkCurrency(Cart $cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module))
                foreach ($currencies_module as $currency_module)
                        if ($currency_order->id == $currency_module['id_currency'])
                                return true;
        return false;
    }
    
    /**
     * Returns HTML code for module settings form
     * @return string
     */
    private function renderForm() {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'saveDotpayConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
                                .'&configure='.$this->name
                                .'&tab_module='.$this->tab
                                .'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
                'fields_value' => $this->getConfigFormValues(),
                'languages' => $this->context->controller->getLanguages(),
                'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }
    
    /**
     * Returns array data for Prestashop Form Helper
     * @return array
     */
    private function getConfigForm() {
        $optionsApi = array(
              array(
                'id_option2' => 'dev', 
                'name2' => $this->l('dev') 
              ),
              array(
                'id_option2' => 'legacy',
                'name2' => $this->l('legacy')
              )
        );
        $optionsCalculateMethod = array(
              array(
                'id_option2' => '1', 
                'name2' => $this->l('%') 
              ),
              array(
                'id_option2' => '0',
                'name2' => $this->l('amount')
              )
        );
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-wrench',
                ),
                'input' => array(
				
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Activate'),
                        'name' => $this->config->getDotpayEnabledFN(),
                        'is_bool' => true,
                        'required' => true, 
                        'desc' => $this->l('You can hide Dotpay gateway without uninstalling'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'select',
                        'class' => 'fixed-width-xxl',
                        'label' => $this->l('Select used Dotpay API version'),
                        'name' => $this->config->getDotpayApiVersionFN(),
                        'desc' => $this->l('dev is recommended'),
                        'required' => true,
                        'class' => 'api-select',
                        'options' => array(
                            'query' => $optionsApi,
                            'id' => 'id_option2', 
                            'name' => 'name2'
                        )
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayIdFN(),
                        'label' => $this->l('ID'),
                        'size' => 6, 
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('The same as in Dotpay user panel').' <div id="infoID" /></div>',
                        'required' => true						
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayPINFN(),
                        'label' => $this->l('PIN'),
                        'class' => 'fixed-width-lg',
                        'desc' => $this->l('The same as in Dotpay user panel').' <div id="infoPIN" /></div>',
                        'required' => true
                    ),
                    array(
                        'type' => 'switch',
                        'label' => '<span class="dev-option">'.$this->l('Test mode').'</span>',
                        'name' => $this->config->getDotpayTestModeFN(),
                        'is_bool' => true,
                        'class' => 'dev-option',
						'desc' => $this->l('I\'m using Dotpay test account (test ID)').'<br>'.$this->l('Required Dotpay test account:').' <a href="https://ssl.dotpay.pl/test_seller/test/registration/" target="_blank" title="'.$this->l('Dotpay test account registration').'"><b>'.$this->l('registration').'</b></a>',

                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => '<span class="lastInSection">'.$this->l('Renew payment enabled')."</span>",
                        'name' => $this->config->getDotpayRenewFN(),
                        'is_bool' => true,
                        'class' => 'dev-option',
                        'desc' => $this->l('Logged in clients can resume interrupted payments'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => '<span class="dev-option">'.$this->l('OneClick channel enabled').'</span>',
                        'name' => $this->config->getDotpayOneClickFN(),
                        'is_bool' => true,
                        'desc' => $this->l('You can enable OneClick channel'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => '<span class="dev-option">'.$this->l('Credit card channel enabled').'</span>',
                        'name' => $this->config->getDotpayCreditCardFN(),
                        'is_bool' => true,
                        'desc' => $this->l('You can enable separate credit card channel'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => '<span class="dev-option">'.$this->l('MasterPass channel enabled').'</span>',
                        'name' => $this->config->getDotpayMasterPassFN(),
                        'is_bool' => true,
                        'class' => 'dev-option',
                        'desc' => $this->l('You can enable MasterPass channel'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => '<span class="dev-option">'.$this->l('Blik channel enabled').'</span>',
                        'name' => $this->config->getDotpayBlikFN(),
                        'is_bool' => true,
                        'desc' => $this->l('You can enable Blik channel'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => '<span class="dev-option lastInSection">'.$this->l('Dotpay widget enabled').'</span>',
                        'name' => $this->config->getDotpayWidgetModeFN(),
                        'is_bool' => true,
                        'desc' => $this->l('You can enable Dotpay widget on shop site'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('I have separate ID for foreign currencies'),
                        'name' => $this->config->getDotpayPVFN(),
                        'is_bool' => true,
                        'class' => 'dev-option pv-enable-option',
                        'desc' => $this->l('You can enable separate ID for foreign currencies'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayPvIdFN(),
                        'label' => $this->l('ID for foreign currencies account'),
                        'size' => 6, 
                        'class' => 'fixed-width-sm dev-option pv-option',
                        'desc' => $this->l('Copy from Dotpay user panel').' <div id="infoID" /></div>',
                        'required' => true						
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayPvPINFN(),
                        'label' => $this->l('PIN for foreign currencies account'),
                        'class' => 'fixed-width-lg dev-option pv-option',
                        'desc' => $this->l('Copy from Dotpay user panel').' <div id="infoPIN" /></div>',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayPvCurrenciesFN(),
                        'label' => '<span class="label-tooltip" data-toggle="tooltip" title="'.
                                    $this->l('Please enter currency codes separated by commas, for example: EUR,USD').
                                    '">'.$this->l('Currencies for separate ID').'</span>',
                        'class' => 'fixed-width-lg dev-option pv-option lastInSection',
                        'desc' => $this->l('Currencies used for separate ID')
                    ),
					
					
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayApiUsernameFN(),
                        'label' => '<span class="label-tooltip" data-toggle="tooltip" title="'.
                        $this->l('Required for proper operation One Click and display instructions for Transfer channels (wire transfer data are not passed to the bank and a payer needs to copy or write the data manually)').
                            '">'.$this->l('Dotpay API username').'</span>',
                        'class' => 'fixed-width-lg dev-option',
                        'desc' => $this->l('Your username for Dotpay seller panel')
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayApiPasswordFN(),
                        'label' => $this->l('Dotpay API password'),
                        'class' => 'fixed-width-lg dev-option password-field lastInSection',
                        'desc' => $this->l('Your password for Dotpay seller panel'),
                    ),
					
					
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Extracharge options'),
                        'name' => $this->config->getDotpayExChFN(),
                        'is_bool' => true,
                        'class' => 'dev-option excharge-enable-option',
                        'desc' => $this->l('You can enable extracharge'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayExAmountFN(),
                        'label' => $this->l('Increase amount of order'),
                        'class' => 'fixed-width-lg dev-option exch-option',
                        'desc' => $this->l('Value of additional fee for given currency (eg. 5.23)')
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayExPercentageFN(),
                        'label' => $this->l('Increase percentage of order'),
                        'class' => 'fixed-width-lg dev-option exch-option lastInSection',
                        'desc' => $this->l('Value of additional fee for given currency in % (eg. 1.90)').' <div class="infoAmount" />'.$this->l('Will be chosen larger amount').'</div>',
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Discount for Dotpay selected options'),
                        'name' => $this->config->getDotpayDiscountFN(),
                        'is_bool' => true,
                        'class' => 'dev-option discount-enable-option',
                        'desc' => $this->l('You can enable extracharge'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
				    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayDiscAmountFN(),
                        'label' => $this->l('Reduce amount of order'),
                        'class' => 'fixed-width-lg dev-option discount-option',
                        'desc' => $this->l('Value of amount (in current price)')
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayDiscPercentageFN(),
                        'label' => $this->l('Reduce percentage of order'),
                        'class' => 'fixed-width-lg dev-option discount-option',
                        'desc' => $this->l('Value of discount for given currency in % (eg. 1.90)').' <div class="infoAmount" />'.$this->l('Will be chosen larger amount').'</div>',
                    )
				
                ),
                'submit' => array(
                    'class' => 'center-block',
                    'title' => $this->l('Save'),
                ),
            )
        );
    }
    
    /**
     * Returns settings values
     * @return array
     */
    private function getConfigFormValues() {
        return array(
            $this->config->getDotpayEnabledFN() => $this->config->isDotpayEnabled(),
            $this->config->getDotpayApiVersionFN() => $this->config->getDotpayApiVersion(),
            $this->config->getDotpayIdFN() => $this->config->getDotpayId(),
            $this->config->getDotpayPINFN() => $this->config->getDotpayPIN(),
            $this->config->getDotpayRenewFN() => $this->config->isDotpayRenewEn(),
            $this->config->getDotpayMasterPassFN() => $this->config->isDotpayMasterPass(),
            $this->config->getDotpayBlikFN() => $this->config->isDotpayBlik(),
            $this->config->getDotpayOneClickFN() => $this->config->isDotpayOneClick(),
            $this->config->getDotpayCreditCardFN() => $this->config->isDotpayCreditCard(),
            $this->config->getDotpayWidgetModeFN() => $this->config->isDotpayWidgetMode(),
            $this->config->getDotpayTestModeFN() => $this->config->isDotpayTestMode(),
            $this->config->getDotpayPVFN() => $this->config->isDotpayPV(),
            $this->config->getDotpayPvIdFN() => $this->config->getDotpayPvId(),
            $this->config->getDotpayPvPINFN() => $this->config->getDotpayPvPIN(),
            $this->config->getDotpayPvCurrenciesFN() => $this->config->getDotpayPvCurrencies(),
            $this->config->getDotpayExChFN() => $this->config->getDotpayExCh(),
            $this->config->getDotpayExPercentageFN() => $this->config->getDotpayExPercentage(),
            $this->config->getDotpayExAmountFN() => $this->config->getDotpayExAmount(),
            $this->config->getDotpayDiscountFN() => $this->config->getDotpayDiscount(),
            $this->config->getDotpayDiscAmountFN() => $this->config->getDotpayDiscAmount(),
            $this->config->getDotpayDiscPercentageFN() => $this->config->getDotpayDiscPercentage(),
            $this->config->getDotpayApiUsernameFN() => $this->config->getDotpayApiUsername(),
            $this->config->getDotpayApiPasswordFN() => $this->config->getDotpayApiPassword(),
        );
    }
    
    /**
     * Save configuration
     */
    private function saveConfiguration()
    {
        if (Tools::isSubmit('saveDotpayConfig')) 
        {
            $values = $this->getConfigFormValues();
            $keysToCorrect = array(
                $this->config->getDotpayExAmountFN(),
                $this->config->getDotpayExPercentageFN(),
                $this->config->getDotpayDiscAmountFN(),
                $this->config->getDotpayDiscPercentageFN()
            );
            foreach (array_keys($values) as $key) {
                $value = trim(Tools::getValue($key));
                if(in_array($key, $keysToCorrect))
                    $value = $this->makeCorrectNumber($value);
                Configuration::updateValue($key, $value);
            }
        }
    }
    
    /**
     * Make the number values correct
     * @param float $input
     * @return float
     */
    private function makeCorrectNumber($input) {
        return preg_replace('/[^0-9\.]/', "", str_replace(',', '.', trim($input)));
    }

    /**
     * Added Dotpay new payment status if not exist
     * @return bool
     */
    private function addDotpayNewStatus() {
        if (Validate::isInt($this->config->getDotpayNewStatusId()) AND
           (Validate::isLoadedObject($order_state_new = new OrderState($this->config->getDotpayNewStatusId()))) AND
           Validate::isInt($order_state_new->id)
        )
            return true;

        $order_state_new = new OrderState();

        $order_state_new->name = array();
        foreach (Language::getLanguages() as $language) {
            if (strtolower($language['iso_code']) == 'pl')
                $order_state_new->name[$language['id_lang']] = 'Oczekuje na potwierdzenie płatności z Dotpay';
            else
                $order_state_new->name[$language['id_lang']] = 'Awaiting for Dotpay Payment confirmation';
        }

        $order_state_new->send_email = false;
        $order_state_new->invoice = false;
        $order_state_new->unremovable = false;
        $order_state_new->color = "#900000";
        if (!$order_state_new->add())
            return false;
        $this->config->setDotpayNewStatusId($order_state_new->id);
            
        return true;
    }
    
    /**
     * Added Dotpay virtual product for extracharge option
     * @return bool
     */
    private function addDotpayVirtualProduct() {
        if (Validate::isInt($this->config->getDotpayExchVPid()) AND
           (Validate::isLoadedObject($product = new Product($this->config->getDotpayExchVPid()))) AND
           Validate::isInt($product->id)
        )
            return true;
            
        $product = new Product();
        $product->name = array((int)Configuration::get('PS_LANG_DEFAULT') => 'Online payment');
        $product->link_rewrite = array((int)Configuration::get('PS_LANG_DEFAULT') => 'online-payment');
        $product->visibility = 'none';
        $product->reference = 'DOTPAYFEE';
        $product->price = 0.0;
        $product->is_virtual = 1;
        $product->online_only = 1;
        $product->redirect_type = '404';
        $product->quantity = 9999999;
        $product->id_tax_rules_group = 0;
        $product->active = 1;
        $product->meta_keywords = 'payment';
        $product->id_category = 1;
        $product->id_category_default = 1;
        if (!$product->add())
                return false;
        $product->addToCategories(array(1));
        StockAvailable::setQuantity($product->id,NULL,$product->quantity);
        $this->config->setDotpayExchVPid($product->id);

        return true;
    }
    
    /**
     * Added Dotpay discount for reducing shipping cost
     * @return bool
     */
    private function addDotpayDiscount() {
        if (!Validate::isInt($this->config->getDotpayDiscountId())) {
            $voucher = new Discount();
            $voucher->id_discount_type = Discount::AMOUNT;
            $voucher->name = array((int)Configuration::get('PS_LANG_DEFAULT') => 'Discount for online shopping');
            $voucher->description = array((int)Configuration::get('PS_LANG_DEFAULT') => 'Online payment');
            $voucher->value = 0;
            $voucher->code = md5(date("d-m-Y H-i-s"));
            $voucher->quantity = 9999999;
            $voucher->quantity_per_user = 9999999;
            $voucher->cumulable = 1;
            $voucher->cumulable_reduction = 1;
            $voucher->active = 1;
            $voucher->cart_display = 1;
            $now = time();
            $voucher->date_from = date('Y-m-d H:i:s', $now);
            $voucher->date_to = date('Y-m-d H:i:s', $now + (3600 * 24 * 365.25)*50);
            if (!$voucher->add())
                    return false;
            $this->config->setDotpayDiscountId($voucher->id);
        }
        return true;
    }

    /**
     * Set default configuration during installation
     * @return bool
     */
    private function setDefaultConfig() {
        $this->config->setDotpayEnabled(false)
                     ->setDotpayApiVersion('dev')
                     ->setDotpayId('')
                     ->setDotpayPIN('')
                     ->setDotpayRenew(true)
                     ->setDotpayTestMode(false)
                     ->setDotpayBlik(false)
                     ->setDotpayMasterPass(false)
                     ->setDotpayOneClick(false)
                     ->setDotpayCreditCard(false)
                     ->setDotpayPV(false)
                     ->setDotpayPvId('')
                     ->setDotpayPvPIN('')
                     ->setDotpayPvCurrencies('')
                     ->setDotpayWidgetMode(true)
                     ->setDotpayExCh(false)
                     ->setDotpayExAmount(0)
                     ->setDotpayExPercentage(0)
                     ->setDotpayDiscount(false)
                     ->setDotpayDiscAmount(0)
                     ->setDotpayApiUsername('')
                     ->setDotpayApiPassword('')
                     ->setDotpayDiscPercentage(0);
        return true;
    }
    
    /**
     * Returns variables required by Smarty channel list template
     * @return array
     */
    private function getSmartyVars() {
        $_GET['module'] = $this->name;
        $pc = FrontController::getController('dotpaypaymentModuleFrontController');

        return $pc->getArrayForSmarty();
    }
    
    /**
     * Checks, if SSL is enabled during current connection
     * @return boolean
     */
    public function isSSLEnabled() {
        if(isset($_SERVER['HTTPS'])) {
            if('on' == strtolower($_SERVER['HTTPS']))
                return true;
        } else if(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
            return true;
        }
        return false;
    }
    
    /**
     * Returns URL of current page
     * @return string
     */
    function getUrl() {
        $url = 'http';
        if ($_SERVER["HTTPS"] == "on") {
            $url .= "s";
            
        }
        $url .= "://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        return $url;
    }
}

/**
 * Fix for PHP older than 7.0
 * @param string $dir
 * @param int $levels
 * @return string
 */
function mydirname($dir, $levels) {
    while(--$levels)
        $dir = dirname($dir);
    return $dir;
}
