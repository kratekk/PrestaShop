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

require_once(mydirname(__DIR__,2).'/vendor/simple_html_dom.php');

class DotpayInstruction extends ObjectModel {
    public $instruction_id;
    public $order_id;
    public $number;
    public $hash;
    public $firstname;
    public $lastname;
    public $bank_account;
    public $is_cash;
    public $amount;
    public $currency;
    public $channel;
    
    const DOTPAY_NAME = 'DOTPAY SA';
    const DOTPAY_STREET = 'Wielicka 72';
    const DOTPAY_CITY = '30-552 Kraków';
    
    public static $definition = array(
        'table' => 'dotpay_instructions',
        'primary' => 'instruction_id',
        'fields' => array(
            'order_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'number' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage', 'required' => true),
            'hash' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage', 'required' => true),
            'firstname' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage'),
            'lastname' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage'),
            'bank_account' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage'),
            'is_cash' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'amount' => array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'required' => true),
            'currency' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage', 'required' => true),
            'channel' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
        )
    );
    
    /**
     * Create table for this model
     * @return boolean
     */
    public static function create() {
        return Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::$definition['table'].'` (
                `instruction_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `order_id` INT UNSIGNED NOT NULL,
                `number` varchar(64) NOT NULL,
                `hash` varchar(128) NOT NULL,
                `firstname` VARCHAR(64),
                `lastname` VARCHAR(64),
                `bank_account` VARCHAR(64),
                `is_cash` int(1) NOT NULL,
                `amount` decimal(10,2) NOT NULL,
                `currency` varchar(3) NOT NULL,
                `channel` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`instruction_id`)
            ) DEFAULT CHARSET=utf8;');
    }
    
    public static function getByOrderId($orderId) {
        $result = Db::getInstance()->executeS('
            SELECT instruction_id as id 
            FROM `'._DB_PREFIX_.self::$definition['table'].'` 
            WHERE order_id = '.(int)$orderId
        );
        if(!is_array($result) || count($result)<1)
            return NULL;
        return new DotpayInstruction($result[count($result)-1]['id']);
    }
    
    public static function gethashFromPayment($payment) {
        $parts = explode('/',$payment['instruction']['instruction_url']);
        return $parts[count($parts)-2];
    }
    
    public function getBankPage($baseUrl) {
        $url = $this->buildInstructionUrl($baseUrl);
        $html = file_get_html($url);
        if($html==false)
            return null;
        return $html->getElementById('channel_container_')->firstChild()->getAttribute('href');
    }
    
    public function getPdfUrl($baseUrl) {
        return $baseUrl.'instruction/pdf/'.$this->number.'/'.$this->hash.'/';
    }
    
    protected function buildInstructionUrl($baseUrl) {
        return $baseUrl.'instruction/'.$this->number.'/'.$this->hash.'/';
    }
    
    public function __construct($id = null) {
        parent::__construct($id);
    }
}
