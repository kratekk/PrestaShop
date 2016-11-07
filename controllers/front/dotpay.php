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

require_once(mydirname(__DIR__,3).'/models/Config.php');

/**
 * Abstract controller for other Dotpay plugin controllers
 */
abstract class DotpayController extends ModuleFrontController {
    protected $config;
    protected $customer;
    protected $address;
    protected $api;

    public function __construct() {
        parent::__construct();
        $this->config = new DotpayConfig();
        
        $this->initPersonalData();
        
        if($this->config->getDotpayApiVersion()=='legacy') {
            $this->api = new DotpayLegacyApi($this);
        } else {
            $this->api = new DotpayDevApi($this);
        }
        
        $this->module->registerFormHelper();
    }
    
    /**
     * 
     * @return string
     */
    public function getDotId() {
        return $this->config->getDotpayId();
    }
    
    public function getLastOrderNumber() {
        return Order::getOrderByCartId($this->context->cart->id);
    }


    /**
     * 
     * @return string
     */
    public function getDotControl($source = NULL) {
        if($source == NULL)
            return $this->getLastOrderNumber().'|'.$_SERVER['SERVER_NAME'];
        else {
            $tmp = explode('|', $source);
            return $tmp[0];
        }
    }
    
    /**
     * 
     * @return string
     */
    public function getDotPinfo() {
        return Configuration::get('PS_SHOP_NAME');
    }
    
    /**
     * 
     * @return string
     */
    public function getDotAmount() {
        return $this->api->getFormatAmount(
            Tools::displayPrice(
                $this->context->cart->getOrderTotal(true, Cart::BOTH), new Currency($this->context->cart->id_currency)
            )
        );
    }
    
    /**
     * 
     * @return string
     */
    public function getDotShippingAmount() {
        return $this->api->getFormatAmount(
            Tools::displayPrice(
                $this->context->cart->getOrderTotal(true, Cart::ONLY_SHIPPING), new Currency($this->context->cart->id_currency)
            )
        );
    }
    
    /**
     * 
     * @return string
     */
    public function getDotCurrency() {
        $currency = Currency::getCurrency($this->context->cart->id_currency);
        return $currency["iso_code"];
    }
    
    /**
     * 
     * @return int
     */
    public function getDotCurrencyId() {
        $currency = Currency::getCurrency($this->context->cart->id_currency);
        return $currency["id_currency"];
    }
    
    /**
     * 
     * @return string
     */
    public function getDotDescription() {
        $order = new Order(Order::getOrderByCartId($this->context->cart->id));
        return ("Order ID: ".$order->reference);
    }
    
    /**
     * 
     * @return string
     */
    public function getDotLang() {
        $lang = strtolower(LanguageCore::getIsoById($this->context->cookie->id_lang));
        if (in_array($lang, $this->config->getDotpayAvailableLanguage())) {
            return $lang;
        } else {
            return "en";
        }
    }
    
    public function getServerProtocol() {
        $result = 'http';
        
        if(isset($_SERVER['HTTPS'])) {
            $result = 'https';
        }
        
        return $result;
    }
    
    public function isSSLEnabled() {
        return (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] === '443'));
    }
    
    /**
     * 
     * @return string
     */
    public function getDotUrl() {
        return $this->context->link->getModuleLink('dotpay', 'back', array('control' => $cart->id), $this->isSSLEnabled());
    }
    
    /**
     * 
     * @return string
     */
    public function getDotUrlC() {
        return $this->context->link->getModuleLink('dotpay', 'callback', array('ajax' => '1'), $this->isSSLEnabled());
    }
    
    /**
     * 
     * @return string
     */
    public function getDotFirstname() {
        return $this->customer->firstname;
    }
    
    /**
     * 
     * @return string
     */
    public function getDotLastname() {
        return $this->customer->lastname;
    }
    
    /**
     * 
     * @return string
     */
    public function getDotEmail() {
        return $this->customer->email;
    }
    
    /**
     * 
     * @return string
     */
    public function getDotPhone() {
        $phone = '';
        if($this->address->phone != '')
            $phone = $this->address->phone;
        else if($this->address->phone_mobile != '')
            $phone = $this->address->phone_mobile;
        return $phone;
    }
    
    /**
     * 
     * @return array
     */
    public function getDotStreetAndStreetN1() {
        $street = $this->address->address1;
        $street_n1 = $this->address->address2;
        
        if(empty($street_n1))
        {
            preg_match("/\s[\w\d\/_\-]{0,30}$/", $street, $matches);
            if(count($matches)>0)
            {
                $street_n1 = trim($matches[0]);
                $street = str_replace($matches[0], '', $street);
            }
        }
        
        return array(
            'street' => $street,
            'street_n1' => $street_n1
        );
    }
    
    /**
     * 
     * @return string
     */
    public function getDotCity() {
        return $this->address->city;
    }
    
    /**
     * 
     * @return string
     */
    public function getDotPostcode() {
        return $this->address->postcode;
    }
    
    public function isDotpayPVEnabled() {
        $result = $this->config->isDotpayPV();
        if(!$this->isDotSelectedCurrency($this->config->getDotpayPvCurrencies())) {
            $result = false;
        }
        return $result;
    }
    
    /**
     * 
     * @return string
     */
    public function getDotCountry() {
        $country = new Country((int)($this->address->id_country));
        return $country->iso_code;
    }
    
    public function getDotBlikLogo() {
        return $this->module->getPath().'web/img/BLIK.png';
    }
    
    public function getDotMasterPassLogo() {
        return $this->module->getPath().'web/img/MasterPass.png';
    }
    
    public function getDotOneClickLogo() {
        return $this->module->getPath().'web/img/oneclick.png';
    }
    
    public function getDotPVLogo() {
        return $this->module->getPath().'web/img/oneclick.png';
    }
    
    public function getDotCreditCardLogo() {
        return $this->module->getPath().'web/img/oneclick.png';
    }
    
    public function getDotpayLogo() {
        return $this->module->getPath().'web/img/dotpay.png';
    }
    
    public function getPreparingUrl() {
        return $this->context->link->getModuleLink($this->module->name,'preparing',array());
    }
    
    protected function initPersonalData() {
        if($this->context->cart==NULL)
            $this->context->cart = new Cart($this->context->cookie->id_cart);
        
        $this->address = new Address($this->context->cart->id_address_invoice);
        $this->customer = new Customer($this->context->cart->id_customer);
    }

    protected function isDotSelectedCurrency($allowCurrencyForm) {
        $result = false;
        $paymentCurrency = $this->getDotCurrency();
        $allowCurrency = str_replace(';', ',', $allowCurrencyForm);
        $allowCurrency = strtoupper(str_replace(' ', '', $allowCurrency));
        $allowCurrencyArray =  explode(",",trim($allowCurrency));
        
        if(in_array(strtoupper($paymentCurrency), $allowCurrencyArray)) {
            $result = true;
        }
        
        return $result;
    }
    
    protected function isExVPinCart() {
        $products = $this->context->cart->getProducts(true);
        foreach($products as $product) {
            if($product['id_product'] == $this->config->getDotpayExchVPid())
                return true;
        }
        return false;
    }
}