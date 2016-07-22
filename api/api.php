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
require_once(mydirname(__DIR__,2).'/models/Config.php');
require_once(mydirname(__DIR__,2).'/controllers/front/dotpay.php');
require_once(mydirname(__DIR__,2).'/classes/Curl.php');

/**
 * Interface and common functionality of the API.
 */
abstract class DotpayApi {
    public static $ocChannel = 248;
    public static $pvChannel = 248;
    public static $ccChannel = 246;
    public static $blikChannel = 73;
    public static $mpChannel = 71;
    /**
     *
     * @var DotpayController Controller object 
     */
    protected $parent;
    
    /**
     *
     * @var DotpayConfig DotpayConfig object
     */
    protected $config;

    /**
     *
     * Channels group for cash method
     */
    const cashGroup = 'cash';
    
    /**
     *
     * Channels group for transfers method
     */
    const transfersGroup = 'transfers';
    
    /**
     * 
     * @param DotpayController $parent Owner of the object API.
     */
    public function __construct(DotpayController $parent = NULL) {
        $this->parent = $parent;
        $this->config = new DotpayConfig();
    }
    
    /**
     * Returns amount in correct format
     * @param float $amount
     * @return string
     */
    public function getFormatAmount($amount) {
        $currency = Currency::getCurrency(Context::getContext()->cart->id_currency);
        if (isset($currency['decimals']) && $currency['decimals']==0) {
            if (Configuration::get('PS_PRICE_ROUND_MODE')!=null) {
                switch (Configuration::get('PS_PRICE_ROUND_MODE')) {
                    case 0:
                        $amount = ceil($amount);
                        break;
                    case 1:
                        $amount = floor($amount);
                        break;
                    case 2:
                        $amount = round($amount);
                        break;
                }
            }
        }
        $amount = Tools::displayPrice($amount);
        return preg_replace('/[^0-9.]/', '', str_replace(',', '.', $amount));
    }
    
    /**
     * Returns list of payment channels
     */
    abstract public function getChannelList();
    
    /**
     * Check confirm message from Dotpay
     */
    abstract public function checkConfirm();
    
    /**
     * Returns total amount from confirm message
     */
    abstract public function getTotalAmount();
    
    /**
     * Returns currency from confirm message
     */
    abstract public function getOperationCurrency();
    
    /**
     * Returns operation number from confirm message
     */
    abstract public function getOperationNumber();
    
    /**
     * Returns new order state from confirm message
     */
    abstract public function getNewOrderState();
    
    /**
     * Returns hidden form for Dotpay Helper Form
     */
    abstract public function getHiddenForm();
    
    /**
     * Performs actions on preparing form
     */
    abstract public function onPrepareAction($action, $params);

    /**
     * Check, if channel is in channels groups
     */
    abstract public function isChannelInGroup($channelId, array $groups);
    
    /**
     * Returns CHK for request params
     */
    abstract protected function generateCHK($DotpayId, $DotpayPin, $ParametersArray);
    
    /**
     * Returns amount for extra charge
     */
    abstract public function getExtrachargeAmount();
    
    /**
     * Returns amount for discount for Dotpay
     */
    abstract public function getDiscountAmount();
    
    /**
     * Returns header form for Dotpay Helper Form
     * @param string $formTarget
     * @param string|null $url
     * @return array
     */
    protected function getFormHeader($formTarget, $url = NULL) {
        if($url == NULL)
            $url = $this->config->getDotpayTargetUrl();
        return array(
            'action' => $url,
            'method' => 'post',
            'form-target' => $formTarget,
            'class' => 'dotpay-form'
        );
    }
    
    /**
     * Returns a specific agreements
     * @param string $what
     * @return string
     */
    protected function getAgreements($what) {
        $resultJson = $this->getApiChannels();
        if(false !== $resultJson) {
            $result = json_decode($resultJson, true);
            if (isset($result['forms']) && is_array($result['forms'])) {
                foreach ($result['forms'] as $forms) {
                    if (isset($forms['fields']) && is_array($forms['fields'])) {
                        foreach ($forms['fields'] as $forms1) {
                            if ($forms1['name'] == $what) {
                                $resultStr = $forms1['description_html'];
                            }
                        }
                    }
                }
            }
        }
        return $resultStr;
    }
    
    /**
     * Returns bylaw agreements
     * @return string
     */
    public function getByLaw() {
        $byLawAgreements = $this->getAgreements('bylaw');
        if(trim($byLawAgreements) == ''){
            $byLawAgreements = 'I accept Dotpay S.A. <a title="regulations of payments" target="_blank" href="https://ssl.dotpay.pl/files/regulamin_dotpay_sa_dokonywania_wplat_w_serwisie_dotpay_en.pdf">Regulations of Payments</a>.';
        }
        return $byLawAgreements;
    }
    
    /**
     * Returns personal data agreements
     * @return string
     */
    public function getPersonalData() {
        $personalDataAgreements = $this->getAgreements('personal_data');
        if(trim($personalDataAgreements) == ''){
            $personalDataAgreements = 'I agree to the use of my personal data by Dotpay S.A. 30-552 Kraków (Poland), Wielicka 72 for the purpose of	conducting a process of payments in accordance with applicable Polish laws (Act of 29.08.1997 for the protection of personal data, Dz. U. No 133, pos. 883, as amended). I have the right to inspect and correct my data.';
        }
        return $personalDataAgreements;
    }


    /**
     * Returns channel data, if payment channel is active for order data
     * @param type $id channel id
     * @return array|false
     */
    public function getChannelData($id, $pv = false) {
    $resultJson = $this->getApiChannels($pv);
        if(false !== $resultJson) {
            $result = json_decode($resultJson, true);

            if (isset($result['channels']) && is_array($result['channels'])) {
                foreach ($result['channels'] as $channel) {
                    if (isset($channel['id']) && $channel['id']==$id) {
                        return $channel;
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * Returns string with channels data JSON
     * @return string|boolean
     */
    protected function getApiChannels($pv=false) {
        $dotpayUrl = $this->config->getDotpayTargetUrl();
        $paymentCurrency = $this->parent->getDotCurrency();
        
        if($pv)
            $dotpayId = $this->config->getDotpayPvId();
        else
            $dotpayId = $this->config->getDotpayId();
        
        $orderAmount = $this->parent->getDotAmount();
        
        $dotpayLang = $this->parent->getDotLang();
        
        $curlUrl = "{$dotpayUrl}payment_api/channels/";
        $curlUrl .= "?currency={$paymentCurrency}";
        $curlUrl .= "&id={$dotpayId}";
        $curlUrl .= "&amount={$orderAmount}";
        $curlUrl .= "&lang={$dotpayLang}";
        
        try {
            $curl = new DotpayCurl();
            $curl->addOption(CURLOPT_SSL_VERIFYPEER, false)
                 ->addOption(CURLOPT_HEADER, false)
                 ->addOption(CURLOPT_URL, $curlUrl)
                 ->addOption(CURLOPT_REFERER, $curlUrl)
                 ->addOption(CURLOPT_RETURNTRANSFER, true);
            $resultJson = $curl->exec();
        } catch (Exception $exc) {
            $resultJson = false;
        }
        
        if($curl) {
            $curl->close();
        }
        
        return $resultJson;
    }

        /**
     * Returns one hidden field for Dotpay Helper Form
     * @param string $name
     * @param string $value
     * @return array
     */
    protected function getHiddenField($name, $value) {
        return array(
            'type' => 'hidden',
            'name' => $name,
            'value' => $value
        );
    }
    
    /**
     * Returns submit field for Dotpay Helper Form
     * @return array
     */
    protected function getSubmitField() {
        return array(
            'type' => 'submit',
            'value' => '<span>'.$this->parent->module->l('Continue payment').'<i class="icon-chevron-right right"></i></span>',
            'class' => 'button btn btn-default standard-checkout button-medium',
            'label' => '</p>',
            'llabel' => '<p class="cart_navigation clearfix">'
        );
    }
}