<?php
/**
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
*/

require_once(DOTPAY_PLUGIN_DIR.'/controllers/front/dotpay.php');

/**
 * Controller for handling return address
 */
class dotpaystatusModuleFrontController extends DotpayController
{
    /**
     * Checks a payment status of order in shop
     */
    public function initContent()
    {
        parent::initContent();
        $orderId = Tools::getValue('orderId');
        if ($orderId != null) {
            $order = new Order($orderId);
            $lastOrderState = new OrderState($order->getCurrentState());
            switch ($lastOrderState->id) {
                case $this->config->getDotpayNewStatusId():
                    die('0');
                case _PS_OS_PAYMENT_:
                    $payments = OrderPayment::getByOrderId($orderId);
                    if ((count($payments) - count($order->getBrother())) > 1) {
                        die('2');
                    } else {
                        die('1');
                    }
                default:
                    die('-1');
            }
        } else {
            die('NO');
        }
    }
}
