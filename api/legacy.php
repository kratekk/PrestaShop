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

require_once(__DIR__.'/api.php');

class DotpayLegacyApi extends DotpayApi {
    /**
     * Returns list of payment channels
     * @return array
     */
    public function getChannelList(){
        $channelList = array();
        $targetUrl = $this->parent->getPreparingUrl();
        $channelList['dotpay'] = array(
            'form' => $this->getFormHeader('dotpay', $targetUrl),
            'fields' => array(
                $this->getHiddenField('dotpay_type', 'dotpay'),
                $this->getHiddenField('order_id', (int)Tools::getValue('order_id')),
                $this->getSubmitField(),
            ),
            'image' => $this->parent->getDotpayLogo(),
            'description' => "&nbsp;&nbsp;<strong>".$this->parent->module->l(" Dotpay ")."</strong>&nbsp;<span>".$this->parent->module->l("(fast and secure internet payment)")."</span>",
        );
        return $channelList;
    }
    
    /**
     * Check confirm message from Dotpay
     * @return bool
     */
    public function checkConfirm(){
        $signature =
        $this->config->getDotpayPIN().":".
        $this->config->getDotpayId().":".
        Tools::getValue('control').":".
        Tools::getValue('t_id').":".
        Tools::getValue('amount').":". 
        Tools::getValue('email').":".
        Tools::getValue('service').":".  
        Tools::getValue('code').":".
        Tools::getValue('username').":".
        Tools::getValue('password').":".
        Tools::getValue('t_status');
	return (Tools::getValue('md5') == hash('md5', $signature));
    }
    
    /**
     * Returns total amount from confirm message
     * @return string|bool
     */
    public function getTotalAmount() {
        $fullAmount = explode(' ', Tools::getValue('orginal_amount'));
        return $fullAmount[0];
    }
    
    public function getOperationCurrency() {
        $fullAmount = explode(' ', Tools::getValue('orginal_amount'));
        return $fullAmount[1];
    }
    
    /**
     * Returns operation number from confirm message
     * @return string|bool
     */
    public function getOperationNumber() {
        return Tools::getValue('t_id');
    }
    
    /**
     * Returns new order state from confirm message
     * @return type
     */
    public function getNewOrderState() {
        $actualState = NULL;
        switch (Tools::getValue('t_status')) {
            case 1:
                $actualState = $this->config->getDotpayNewStatusId();
                break;
            case 2:
                $actualState = _PS_OS_PAYMENT_;
                break;
            case 3:
                $actualState = _PS_OS_ERROR_;
                break;
            case 4:
                $actualState = _PS_OS_ERROR_;
                break;
        }
        return $actualState;
    }

    /**
     * Returns hidden form for Dotpay Helper Form
     * @return array
     */
    public function getHiddenForm() {
        $formFields = array();
        $fields = $this->getHiddenFields();
        foreach($fields as $name => $value) {
            if(Tools::getValue($name)!==false)
                $value = Tools::getValue($name);
            $formFields[] = $this->getHiddenField($name, $value);
        }
        $formFields[] = $this->getHiddenField('chk', $this->generateCHK($this->config->getDotpayID(), $this->config->getDotpayPIN(), $fields));
        if(Tools::getValue('order_id'))
            $formFields[] = $this->getHiddenField('order_id', Tools::getValue('order_id'));
        return array(
            'form'=>  $this->getFormHeader($type),
            'fields' => $formFields
        );
    }
    
    /**
     * Performs actions on preparing form
     * @param string $action
     * @param array $params
     * @return boolean
     */
    public function onPrepareAction($action, $params) {
        return true;
    }
    
    /**
     * Check, if channel is in channels groups
     * @param int $channelId
     * @param array $group
     * @return boolean
     */
    public function isChannelInGroup($channelId, array $groups) {
        return false;
    }
    
    /**
     * Returns standard hidden fields
     * @return array
     */
    private function getHiddenFields() {
        $streetData = $this->parent->getDotStreetAndStreetN1();
        return array(
            'id' => $this->parent->getDotId(),
            'amount' => $this->parent->getDotAmount(),
            'currency' => $this->parent->getDotCurrency(),
            'description' => $this->parent->getDotDescription(),
            'url' => $this->parent->getDotUrl(),
            'urlc' => $this->parent->getDotUrlC(),
            'type' => 0,
            'control' => $this->parent->getDotControl(),
            'p_info' => $this->parent->getDotPinfo(),
            'firstname' => $this->parent->getDotFirstname(),
            'lastname' => $this->parent->getDotLastname(),
            'email' => $this->parent->getDotEmail(),
            'phone' => $this->parent->getDotPhone(),
            'street' => $streetData['street'],
            'street_n1' => $streetData['street_n1'],
            'postcode' => $this->parent->getDotPostcode(),
            'city' => $this->parent->getDotCity(),
            'country' => $this->parent->getDotCountry(),
            'api_version' => $this->config->getDotpayApiVersion(),
        );
    }
    
    /**
     * Returns CHK for request params
     * @param string $DotpayId Dotpay shop ID
     * @param string $DotpayPin Dotpay PIN
     * @param array $ParametersArray Parameters from request
     * @return string
     */
    protected function generateCHK($DotpayId, $DotpayPin, $ParametersArray) {
        $ChkParametersChain =
        $DotpayId.
        (isset($ParametersArray['amount']) ?
        $ParametersArray['amount'] : null).
        (isset($ParametersArray['currency']) ?
        $ParametersArray['currency'] : null).
        (isset($ParametersArray['description']) ?
        $ParametersArray['description'] : null).
        (isset($ParametersArray['control']) ?
        $ParametersArray['control'] : null).
        $DotpayPin;
        return hash('md5', $ChkParametersChain);
    }
    
    /**
     * Returns flag, if was selected PV channel
     */
    public function isSelectedPvChannel() {
        return false;
    }
    
    /**
     * Returns amount after extra charge
     * @return type
     */
    public function getExtrachargeAmount() {
        return 0.0;
    }
    
    /**
     * Returns amount after discount for Dotpay
     * @return type
     */
    public function getDiscountAmount() {
        return 0.0;
    }
}
