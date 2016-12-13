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

require_once(DOTPAY_PLUGIN_DIR.'/models/Config.php');
require_once(DOTPAY_PLUGIN_DIR.'/controllers/front/dotpay.php');
require_once(DOTPAY_PLUGIN_DIR.'/classes/SellerApi.php');

abstract class DotpayRegisterOrder
{
    /**
     *
     * @var DotpayController Controller object
     */
    private static $parent;
    
    /**
     *
     * @var DotpayConfig DotpayConfig object
     */
    private static $config;
    
    /**
     *
     * @var string Target url for Register Order
     */
    private static $target = "payment_api/v1/register_order/";
    
    /**
     * Initialize Register Order mechanism
     * @param DotpayController $parent Owner of the object API.
     */
    public static function init(DotpayController $parent = null)
    {
        self::$parent = $parent;
        self::$config = new DotpayConfig();
    }
    
    /**
     * Create register order, if it not exist
     * @param type $channelId Channel identifier
     * @return null|array
     */
    public static function create($channelId)
    {
        $data = str_replace('\\/', '/', Tools::jsonEncode(self::prepareData($channelId)));
        if (!self::checkIfCompletedControlExist(self::$parent->getDotControl(), $channelId)) {
            return self::createRequest($data);
        }
        return null;
    }
    
    /**
     * Create request without checking conditions
     * @param array $data
     * @return boolean
     */
    private static function createRequest($data)
    {
        try {
            $curl = new DotpayCurl();
            $curl->addOption(CURLOPT_URL, self::$config->getDotpayTargetUrl().self::$target)
                 ->addOption(CURLOPT_SSL_VERIFYPEER, true)
                 ->addOption(CURLOPT_SSL_VERIFYHOST, 2)
                 ->addOption(CURLOPT_RETURNTRANSFER, 1)
                 ->addOption(CURLOPT_TIMEOUT, 100)
                 ->addOption(CURLOPT_USERPWD, self::$config->getDotpayApiUsername().':'.self::$config->getDotpayApiPassword())
                 ->addOption(CURLOPT_POST, 1)
                 ->addOption(CURLOPT_POSTFIELDS, $data)
                 ->addOption(CURLOPT_HTTPHEADER, array(
                    'Accept: application/json; indent=4',
                    'content-type: application/json'));
            $resultJson = $curl->exec();
            $resultStatus = $curl->getInfo();
        } catch (Exception $exc) {
            $resultJson = false;
        }
        
        if ($curl) {
            $curl->close();
        }
        
        if (false !== $resultJson && $resultStatus['http_code'] == 201) {
            return Tools::jsonDecode($resultJson, true);
        }
        
        return false;
    }
    
    /**
     * Check, if order id from control field is completed
     * @param int $control Order id from control field
     * @return boolean
     */
    private static function checkIfCompletedControlExist($control, $channel)
    {
        $api = new DotpaySellerApi(self::$config->getDotpaySellerApiUrl());
        $payments = $api->getPaymentByOrderId(self::$config->getDotpayApiUsername(), self::$config->getDotpayApiPassword(), $control);
        foreach ($payments as $payment) {
            $onePayment = $api->getPaymentByNumber(self::$config->getDotpayApiUsername(), self::$config->getDotpayApiPassword(), $payment->number);
            if ($onePayment->control == $control && $onePayment->payment_method->channel_id == $channel && $payment->status == 'completed') {
                return true;
            }
        }
        return false;
    }

    /**
     * Prepares the data for query.
     * @param int $channelId
     * @return array
     */
    private static function prepareData($channelId)
    {
        $streetData = self::$parent->getDotStreetAndStreetN1();
        return array (
            'order' => array (
                'amount' => self::$parent->getDotAmount(),
                'currency' => self::$parent->getDotCurrency(),
                'description' => self::$parent->getDotDescription(),
                'control' => self::$parent->getDotControl()
            ),

            'seller' => array (
                'account_id' => self::$config->getDotpayId(),
                'url' => self::$parent->getDotUrl(),
                'urlc' => self::$parent->getDotUrlC(),
            ),

            'payer' => array (
                'first_name' => self::$parent->getDotFirstname(),
                'last_name' => self::$parent->getDotLastname(),
                'email' => self::$parent->getDotEmail(),
                'address' => array(
                    'street' => $streetData['street'],
                    'building_number' => $streetData['street_n1'],
                    'postcode' => self::$parent->getDotPostcode(),
                    'city' => self::$parent->getDotCity(),
                    'country' => self::$parent->getDotCountry()
                )
            ),

            'payment_method' => array (
                'channel_id' => $channelId
            ),

            'request_context' => array (
                'ip' => $_SERVER['REMOTE_ADDR']
            )

        );
    }
}
