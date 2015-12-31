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
*  @author    Piotr Karecki <tech@dotpay.pl>
*  @copyright dotpay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*/

if (!defined('_PS_VERSION_'))
	exit;

function upgrade_module_1_4_9($module)
{
        Configuration::updateValue('DP_TEST', false);
		Configuration::updateValue('DP_CHK', false);
        Configuration::updateValue('DP_SSL', false);
	    
		Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'dotpay_amount` (
				  `i_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  `i_id_order` int(10) unsigned NOT NULL,
				  `i_amount` varchar(16) NOT NULL,
				  `i_currency` varchar(3) NOT NULL,
				  `cookie_checksum` varchar(250) NOT NULL,
				  `i_id_customer` int(10) unsigned DEFAULT NULL,
				  `i_id_connections` int(10) unsigned DEFAULT NULL,
				  `i_suma` varchar(32) NOT NULL,
				  `i_is_guest` int(1) NOT NULL,
				  `i_id_guest` int(10) NOT NULL,
				  `i_secure_key` varchar(64) NOT NULL,
				  `i_datetime_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
				) CHARSET=utf8');	
		Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'dotpay_amount` ADD UNIQUE INDEX (`i_suma`)');					
	
	return $module;
}
