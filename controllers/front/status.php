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
    public function init() {
        parent::init();
        header('Content-Type: application/json; charset=utf-8');
        $orderId = Tools::getValue('orderId');
        if ($orderId != null) {
            $order = new Order($orderId);
            $lastOrderState = new OrderState($order->getCurrentState());
            $statusName = (gettype($lastOrderState->name) == 'array')?$lastOrderState->name[1]:$lastOrderState->name;
            switch ($lastOrderState->id) {
                case $this->config->getDotpayNewStatusId():
                    die($this->getJson(1, $statusName));
                case _PS_OS_PAYMENT_:
                    $payments = OrderPayment::getByOrderId($orderId);
                    if ((count($payments) - count($order->getBrother())) > 1) {
                        die($this->getJson(3, $statusName));
                    } else {
                        die($this->getJson(2, $statusName));
                    }
                case _PS_OS_ERROR_:
                    die($this->getJson(0, $statusName));
                default:
                    die($this->getJson(4, $statusName));
            }
        } else {
            die($this->getJson(-1));
        }
    }
    
    
    private function getJson($code, $status = NULL, $message = NULL) {
        $data = [
            "code" => (int)$code,
            "status" => (string)$status
        ];
        if($message !== NULL) {
            $data['message'] = $message;
        }
        if (function_exists('json_encode')) {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $json = str_replace('\\/', '/', Tools::jsonEncode($data));
        }
        return $json;
    }
}
