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
 * Controller for handling return address
 */
class dotpaystatusModuleFrontController extends DotpayController {

    public function initContent() {
        $cookie = new Cookie('lastOrder');
        if($cookie->orderId != null) {
            $lastOrderState = OrderHistory::getLastOrderState($cookie->orderId);
            switch($lastOrderState->id) {
                case $this->config->getDotpayNewStatusId():
                    if(Tools::getValue('lastRequest')===true)
                        $cookie->logout();
                    die('0');
                case _PS_OS_PAYMENT_:
                    $cookie->logout();
                    die('1');
                default:
                    die('-1');
            }
        } else {
            die('NO');
        }
    }
}