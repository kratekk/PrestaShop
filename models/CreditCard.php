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

class DotpayCreditCard extends ObjectModel {
    public $cc_id;
    public $order_id;
    public $customer_id;
    public $mask;
    public $brand;
    public $hash;
    public $card_id;
    public $register_date;
    
    public static $definition = array(
        'table' => 'dotpay_credit_cards',
        'primary' => 'cc_id',
        'fields' => array(
            'order_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'customer_id' => array('type' => self::TYPE_STRING, 'validate' => 'isUnsignedId', 'required' => true),
            'mask' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage'),
            'brand' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage'),
            'hash' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage', 'required' => true),
            'card_id' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage'),
            'register_date' => array('type' => self::TYPE_DATE),
        )
    );
    
    /**
     * Create table for this model
     * @return boolean
     */
    public static function create() {
        return Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::$definition['table'].'` (
                `cc_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `order_id` INT UNSIGNED NOT NULL,
                `customer_id` INT UNSIGNED NOT NULL,
                `mask` varchar(20) DEFAULT NULL,
                `brand` varchar(20) DEFAULT NULL,
                `hash` varchar(100) NOT NULL,
                `card_id` VARCHAR(128) DEFAULT NULL,
                `register_date` DATE DEFAULT NULL,
                PRIMARY KEY (`cc_id`),
                UNIQUE KEY `hash` (`hash`),
                UNIQUE KEY `cc_order` (`order_id`),
                UNIQUE KEY `card_id` (`card_id`),
                KEY `customer_id` (`customer_id`),
                CONSTRAINT fk_customer_id
                    FOREIGN KEY (customer_id)
                    REFERENCES `'._DB_PREFIX_.Customer::$definition['table'].'` (`'.Customer::$definition['primary'].'`)
                    ON DELETE CASCADE
            ) DEFAULT CHARSET=utf8;');
    }
    
    /**
     * Drop table for this model
     * @return boolean
     */
    public static function drop() {
        return Db::getInstance()->execute('
            DROP TABLE IF EXISTS `'._DB_PREFIX_.self::$definition['table'].'`;
        ');
    }
    
    /**
     * Saves current object to database (add or update)
     * @param bool $null_values
     * @param bool $auto_date
     * 
     * @return bool Insertion result
     * @throws PrestaShopException
     */
    public function save($null_values = true, $auto_date = true) {
        $this->register_date = DotpayCreditCard::reverseDate($this->register_date);
        if($this->hash == NULL) {
            $hash = $this->getUniqueCardHash();
            if($hash) {
                $this->hash = $hash;
            } else {
                return false;
            }
        }
        return parent::save($null_values, $auto_date);
    }
    
    /**
     * Get all cards for customer
     * @param int $userId
     * @param boolean $empty
     * @return array
     */
    public static function getAllCardsForCustomer($userId, $empty = false) {
        $not = ($empty) ? '' : 'NOT';
        $ids = Db::getInstance()->ExecuteS('
            SELECT cc_id as id
            FROM `'._DB_PREFIX_.self::$definition['table'].'` 
            WHERE customer_id = '.(int)$userId.' 
            AND 
            card_id IS '.$not.' NULL
        ');
        $cards = array();
        foreach($ids as $id) {
            $cards[] = new DotpayCreditCard($id['id']);
        }
        return $cards;
    }
    
    public static function getCreditCardByOrder($order) {
        $card = Db::getInstance()->ExecuteS('
            SELECT cc_id as id
            FROM `'._DB_PREFIX_.self::$definition['table'].'` 
            WHERE order_id = '.(int)$order
        );
        return new DotpayCreditCard($card[0]['id']);
    }

    /**
     * Delete all cards for customer
     * @param int $userId
     * @param boolean $empty
     * @return boolean
     */
    public static function deleteAllCardsForCustomer($userId) {
        return Db::getInstance()->delete(self::$definition['table'], '`customer_id` = '.(int)$userId);
    }
    
    /**
     * Delete all cards for non existing customers
     * @return boolean
     */
    public static function deleteAllCardsForNonExistingCustomers() {
        return Db::getInstance()->execute('
            DELETE 
            FROM `'._DB_PREFIX_.self::$definition['table'].'` 
            WHERE customer_id NOT IN (
                SELECT id_customer 
                FROM `'._DB_PREFIX_.Customer::$definition['table'].'`
            )
        ');
    }
    
    public static function reverseDate($date) {
        $tmp = explode('-', $date);
        return implode('-', array_reverse($tmp));
    }


    /**
     * Generate card hash for OneClick
     * @return string
     */
    private function generateCardHash() {
        $microtime = '' . microtime(true);
        $md5 = md5($microtime);

        $mtRand = mt_rand(0, 11);

        $md5Substr = substr($md5, $mtRand, 21);

        $a = substr($md5Substr, 0, 6);
        $b = substr($md5Substr, 6, 5);
        $c = substr($md5Substr, 11, 6);
        $d = substr($md5Substr, 17, 4);

        return "{$a}-{$b}-{$c}-{$d}";
    }
    
    /**
     * Check, if generated card hash is unique
     * @return string|boolean
     */
    private function getUniqueCardHash() {
        $count = 200;
        $result = false;
        do {
            $cardHash = $this->generateCardHash();
            $test = Db::getInstance()->ExecuteS('
                SELECT count(*) as count  
                FROM `'._DB_PREFIX_.self::$definition['table'].'` 
                WHERE hash = \''.$cardHash.'\'
            ');
            
            if ($test[0]['count'] == 0) {
                $result = $cardHash;
                break;
            }

            $count--;
        } while ($count);
        
        return $result;
    }
    
    /**
     * 
     * @param int|null $id Credit card object id
     */
    public function __construct($id = null) {
        parent::__construct($id);
         $this->register_date = DotpayCreditCard::reverseDate($this->register_date);
    }
}