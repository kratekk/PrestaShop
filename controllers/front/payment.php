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

require_once(__DIR__.'/dotpay.php');

/**
 * Controller for handling payment view
 */
class dotpaypaymentModuleFrontController extends DotpayController {

    public function initContent() {
        $this->display_column_left = false;
        parent::initContent();
        
        if(Tools::getValue('order_id')) {
            $cart = Cart::getCartByOrderId(Tools::getValue('order_id'));
            if(empty($cart))
                $cart = new Cart();
            $this->context->cart = $cart;
            $this->context->cart->update();
        }
        
        $this->context->smarty->assign($this->getArrayForSmarty(true));
        $this->setTemplate("payment.tpl");
    }
    
    public function getArrayForSmarty($inCheckout = false) {
        $channelList = $this->api->getChannelList();
        $target = '';
        $disabledChannels = array();
        if($this->config->isDotpayBlik())
            $disabledChannels[] = DotpayApi::$blikChannel;
        if($this->config->isDotpayCreditCard())
            $disabledChannels[] = DotpayApi::$ccChannel;
        if($this->config->isDotpayMasterPass())
            $disabledChannels[] = DotpayApi::$mpChannel;
        if($this->config->isDotpayPV())
            $disabledChannels[] = DotpayApi::$pvChannel;
        
        return array(
            'targetUrl' => $target,
            'meta_title' => $this->module->l('Dotpay online payment'),
            'channelList' => $channelList,
            'isWidget' => (bool)($this->config->isDotpayWidgetMode()&&$this->config->getDotpayApiVersion()=='dev'),
            'userId' => $this->config->getDotpayId(),
            'currency' => $this->getDotCurrency(),
            'amount' => $this->getDotAmount(),
            'lang' => $this->getDotLang(),
            'widgetUrl' => $this->module->getPath().'web/js/payment_widget.js',
            'dotpayUrl' => $this->config->getDotpayTargetUrl(),
            'exMessage' => $this->module->l('This payment will be increased by'),
            'exAmount' => $this->api->getExtrachargeAmount(),
            'currency' => $this->getDotCurrency(),
            'discMessage' => $this->module->l('This payment will be reduced by'),
            'discAmount' => $this->api->getDiscountAmount(),
            'orderId' => Tools::getValue('order_id'),
            'goodCurency' => in_array($this->getDotCurrency(), $this->config->getDotpayAvailableCurrency()),
            'disabledChannels' => implode(',',$disabledChannels),
            'channelApiUrl' => $this->config->getDotpayTargetUrl().'payment_api/v1/channels/',
            'inCheckout' => $inCheckout
        );
    }
}
