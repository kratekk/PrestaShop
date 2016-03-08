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
*  @author    Tech Dotpay <tech@dotpay.pl>
*  @copyright Dotpay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*}

{literal}
<style type="text/css">
{/literal}
{if $DP_CHANNELS_VIEW_MAIN == 4}
{literal}
p.payment_module a.dotpay {
    background: url("{/literal}{$DP_ONE_CHANNEL_IMG_MAIN}{literal}") 5px 12px no-repeat #fbfbfb;
    background-size: 120px 60px; 
	}
{/literal}	
{/if}
{literal}	
p.payment_module a.dotpay:after {
    display: block;
    content: "\f054";
    position: absolute;
    right: 15px;
    margin-top: -11px;
    top: 50%;
    font-family: "FontAwesome";
    font-size: 25px;
    height: 22px;
    width: 14px;
    color: #777777; }	
	
</style>	
{/literal}

{if $DP_CHANNELS_VIEW_MAIN == 4}
<div class="row">
	<div class="col-xs-12 col-md-12">
		<p class="payment_module">
			<a class="dotpay" href="{$link->getModuleLink('dotpay', 'payment')|escape:'html'}" title="{l s='Pay by dotpay' mod='dotpay'}">
			<span style="margin-left: 30px;">&nbsp;</span>{l s='Pay by ' mod='dotpay'}{$DP_ONE_CHANNEL_NAME_MAIN}&nbsp;<span>{l s='( with dotpay.pl)' mod='dotpay'}</span> 
					</a>
		</p>
    </div>
</div>
{else}
<div class="row">
	<div class="col-xs-12 col-md-12">
		<p class="payment_module">
			<a class="dotpay" href="{$link->getModuleLink('dotpay', 'payment')|escape:'html'}" title="{l s='Pay by dotpay' mod='dotpay'}">
				{l s='Pay by dotpay' mod='dotpay'}&nbsp;<span>{l s='(fast and secure internet payment)' mod='dotpay'}</span>
					</a>
		</p>
    </div>
</div>
{/if}