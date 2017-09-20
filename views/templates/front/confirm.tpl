{*
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
*}

{capture name=path}
	{l s='Completion of payments' mod='dotpay'}
{/capture}

<h2 class="page-heading">{l s='Payment instruction' mod='dotpay'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $errorMessage}
    <p class="alert alert-danger">{$errorMessage|escape:'htmlall':'UTF-8'}</p>
{/if}

{if $isOk}
<p class="alert alert-success">{l s='Payment has been initialized' mod='dotpay'}</p>

{include file=$template}

{else}
    <p class="alert alert-danger">{l s='Payment is not found or registered' mod='dotpay'}</p>
{/if}