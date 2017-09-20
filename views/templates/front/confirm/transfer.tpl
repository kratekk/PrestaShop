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

<section id="instruction">
    <div class="row">
        <div class="col-xs-12">
            <p id="instruction-content">{l s='In order to pay login to your bank system and make a money transfer, using the following data:' mod='dotpay'}</p>
        </div>
        <div class="col-md-4 col-md-offset-1">
            <label>
                {l s='Account number' mod='dotpay'}
                <input type="text" class="important" id="iban" value="{$bankAccount|escape:'htmlall':'UTF-8'}" readonly />
            </label>
            <label>
                {l s='Amount of payment' mod='dotpay'}
                <div class="input-group">
                    <input type="text" class="important" id="amount" value="{$amount|escape:'htmlall':'UTF-8'}" aria-describedby="transfer-currency" readonly >
                    <span class="input-group-addon" id="transfer-currency">{$currency|escape:'htmlall':'UTF-8'}</span>
                </div>
            </label>
            <label>
                {l s='Title of payment' mod='dotpay'}
                <input type="text" class="important" id="payment-title" value="{$title|escape:'htmlall':'UTF-8'}" readonly />
            </label>
        </div>
        <div class="col-md-4 col-md-offset-2">
            <label>
                {l s='Name of recipient' mod='dotpay'}
                <input type="text" class="important" id="recipient" value="{$recipient|escape:'htmlall':'UTF-8'}" readonly />
            </label>
            <label>
                {l s='Street' mod='dotpay'}
                <input type="text" class="important" id="street" value="{$street|escape:'htmlall':'UTF-8'}" readonly />
            </label>
            <label>
                {l s='Post code and city' mod='dotpay'}
                <input type="text" class="important" id="post-code-city" value="{$city|escape:'htmlall':'UTF-8'}" readonly />
            </label>
        </div>
    </div>
    <div class="row">
        <section id="payment-form" class="col-xs-12">
            <div id="blankiet-download-form">
                <div id="channel_container_confirm">
                    <a href="{$address}" target="_blank" title="{l s='Make a money transfer' mod='dotpay'}">
                        <div>
                            <img src="{$channelImage}" alt="{l s='Your bank logo' mod='dotpay'}" />
                            <span>{l s='Make a money transfer' mod='dotpay'}</span>
                        </div>
                    </a>
                </div>
            </div>
        </section>
        <div class="col-xs-12">
            <p class="alert alert-info">{l s='Not following the above procedure (e.g. changing the amount or payment title) will make your payment not handled automatically, and therefore lengthen finalization time of the transaction.' mod='dotpay'}</p>
        </div>
    </div>
</section>

