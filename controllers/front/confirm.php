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
require_once(mydirname(__DIR__,3).'/classes/RegisterOrder.php');

/**
 * Controller for display confirm from Register Order
 */
class dotpayconfirmModuleFrontController extends DotpayController {
    /**
     * Display payment instruction for cash or transfer payments
     */
    public function initContent() {
        $this->display_column_left = false;
        parent::initContent();
        $errorMessage = NULL;

        if(Tools::getValue('order_id')) {
            $this->context->cart = Cart::getCartByOrderId(Tools::getValue('order_id'));
            $this->initPersonalData();
        }
        
        $channel = $this->context->cookie->dotpay_channel;
        unset($this->context->cookie->dotpay_channel);
        DotpayRegisterOrder::init($this);
        $payment = DotpayRegisterOrder::create($channel);
        if($payment===NULL) {
            $instruction = DotpayInstruction::getByOrderId(Tools::getValue('order_id'));
            if(!empty($instruction) && $instruction->id == NULL) {
                $this->context->smarty->assign(array(
                    'isOk' => false
                ));
            }
        } else {
            if(isset($payment['instruction']) && isset($payment['operation'])) {
                if($this->api->isChannelInGroup($payment['operation']['payment_method']['channel_id'], array(DotpayApi::cashGroup))) {
                    $isCash = true;
                } else {
                    $isCash = false;
                }
                
                $instruction = new DotpayInstruction();
                $instruction->amount = $payment['instruction']['amount'];
                $instruction->currency = $payment['instruction']['currency'];
                $instruction->number = $payment['operation']['number'];
                $instruction->hash = DotpayInstruction::gethashFromPayment($payment);
                $instruction->is_cash = $isCash;
                $instruction->order_id = Tools::getValue('order_id');
                $instruction->channel = $payment['operation']['payment_method']['channel_id'];
                
                if(isset($payment['instruction']['recipient'])) {
                    $instruction->bank_account = $payment['instruction']['recipient']['bank_account_number'];
                }
                
                try{
                    $instruction->save();
                } catch(Exception $e){
                    $this->context->smarty->assign(array(
                        'errorMessage' => $this->module->l("Unable to save instructions.".$e->getMessage())
                    ));
                }
            }
        }
        
        if(!empty($instruction) && $instruction->id != NULL) {
            if($instruction->is_cash) {
                $template = 'cash.tpl';
                $address = $instruction->getPdfUrl($this->config->getDotpayTargetUrl());
                $bankImage = '';
            } else {
                $template = 'transfer.tpl';
                $address = $instruction->getBankPage($this->config->getDotpayTargetUrl());
            }
            $chData = $this->api->getChannelData($instruction->channel);
            $channelImage = $chData['logo'];
            $this->context->smarty->assign(array(
                'meta_title' => $this->module->l('Complete payment'),
                'isOk' => true,
                'amount' => $instruction->amount,
                'currency' => $instruction->currency,
                'title' => $instruction->number,
                'address' => $address,
                'bankAccount' => $instruction->bank_account,
                'recipient' => DotpayInstruction::DOTPAY_NAME,
                'street' => DotpayInstruction::DOTPAY_STREET,
                'city' => DotpayInstruction::DOTPAY_CITY,
                'template' => './confirm/'.$template,
                'channelImage' =>$channelImage
            ));
        }
        
        $this->setTemplate("confirm.tpl");
    }
}
