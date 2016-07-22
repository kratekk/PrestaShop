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
 * Controller for handling callback from Dotpay
 */
class dotpaycallbackModuleFrontController extends DotpayController {
    public function displayAjax() {
        if($_SERVER['REMOTE_ADDR'] == $this->config->getOfficeIp() && $_SERVER['REQUEST_METHOD'] == 'GET')
            die("PrestaShop - M.Ver: ".$this->module->version.
                ", P.Ver: ". _PS_VERSION_ .
                ", ID: ".$this->config->getDotpayId().
                ", Active: ".(bool)$this->config->isDotpayEnabled().
                ", Test: ".(bool)$this->config->isDotpayTestMode()
            );
        
        if($_SERVER['REMOTE_ADDR'] != $this->config->getDotpayIp())
            die("PrestaShop - ERROR (REMOTE ADDRESS: ".$_SERVER['REMOTE_ADDR'].")");

        if ($_SERVER['REQUEST_METHOD'] != 'POST')
            die("PrestaShop - ERROR (METHOD <> POST)");
        
        if (!$this->api->checkConfirm())
            die("PrestaShop - ERROR SIGN");
        
        if (Tools::getValue('id') != $this->config->getDotpayId())
            die("PrestaShop - ERROR ID");
        
        $order = new Order((int)$this->getDotControl(Tools::getValue('control')));
        $currency = new Currency($order->id_currency);
        
        $receivedCurrency = $this->api->getOperationCurrency();
        $orderCurrency = $currency->iso_code;
        
        if($receivedCurrency != $orderCurrency) 
            die('PrestaShop - NO MATCH OR WRONG CURRENCY - '.$receivedCurrency.' <> '.$orderCurrency);
        
        $receivedAmount = floatval($this->api->getTotalAmount());
        $orderAmount = Tools::displayPrice($order->total_paid, $currency, false);
        $orderAmount = floatval(
            $this->getCorrectAmount(
                preg_replace("/[^-0-9\.]/","",str_replace(',','.',$orderAmount))
            )
        );
        
        if($receivedAmount != $orderAmount) 
            die('PrestaShop - NO MATCH OR WRONG AMOUNT - '.$receivedAmount.' <> '.$orderAmount);
        
        $newOrderState = $this->api->getNewOrderState();
        if($newOrderState===NULL)
            die ('PrestaShop - WRONG TRANSACTION STATUS');
        
        $cc = DotpayCreditCard::getCreditCardByOrder($order->id);
        if($cc->id !== NULL && $cc->card_id == NULL) {
            $sellerApi = new DotpaySellerApi($this->config->getDotpaySellerApiUrl());
            $ccInfo = $sellerApi->getCreditCardInfo(
                $this->config->getDotpayApiUsername(),
                $this->config->getDotpayApiPassword(),
                $this->api->getOperationNumber()
            );
            $cc->brand = $ccInfo->brand->name;
            $cc->mask = $ccInfo->masked_number;
            $cc->card_id = $ccInfo->id;
            $cc->save();
        }
        
        $history = new OrderHistory();
        $history->id_order = $order->id;
        $lastOrderState = OrderHistory::getLastOrderState($history->id_order);
        if($lastOrderState->id != $newOrderState) {
            $history->changeIdOrderState($newOrderState, $history->id_order);
            $history->addWithemail(true);
            if($newOrderState == _PS_OS_PAYMENT_) {
                $payments = OrderPayment::getByOrderId($order->id);
                if(count($payments)) {
                    $payments[0]->transaction_id = $this->api->getOperationNumber();
                    $payments[0]->payment_method = $this->module->displayName;
                    $payments[0]->update();
                }
                DotpayInstruction::getByOrderId($order->id)->delete();
            }
        }
        else if($lastOrderState->id == $newOrderState && $newOrderState == _PS_OS_PAYMENT_) {
            die ('PrestaShop - ORDER IS ALERADY PAID');
        }
        die('OK');
    }
    
    private function getCorrectAmount($amount) {
        $count = 0;
        do {
            $amount = preg_replace("/(\d+)\.(\d{3,})/", "$1$2", $amount, -1, $count);
        } while($count > 0);
        return $amount;
    }
}
