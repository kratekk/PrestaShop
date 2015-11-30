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
*  @author    Dotpay <tech@dotpay.pl>
*  @copyright Dotpay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  @last modified: Dotpay: 2015-11-30
*   
*
*}

{if $dPorder_summary != 1}
<div class="row">
	<div class="col-xs-12">
		<p class="payment_module">
		
			<div class="box" style="overflow: auto; background: #FBFBFB none repeat scroll 0% 0%; border: 1px solid #D6D4D4; padding: 14px 18px 13px; margin: 0px 0px 30px; line-height: 23px;">
				<div class="row">
					 <div class="col-sm-6">
						<img src="{$module_dir}img/Dotpay_logo_napis{if $lang_iso == 'pl'}_pl{else}_en{/if}.png" width="250px" />
					 </div>
					 <div class="col-sm-6">
					 <h4>{l s='Pay by dotpay' mod='dotpay'}&nbsp;<em>{l s='(fast and secure internet payment)' mod='dotpay'}</em></h4>
						<h3>{l s='Order number ' mod='dotpay'}{$params['control']}{l s=' of ' mod='dotpay'}{$params['amount']} {$params['currency']}</h3>
					 </div>
				</div>
				 <hr>
				{l s='Select the "Confirm purchase" to confirm your order and go to dotpay.pl where you can complete your payment.' mod='dotpay'}
			</div>
		</p>
    </div>
</div>


<form action="{$form_url}" method="post" id="dpForm" name="dpForm" target="_parent">
{foreach from=$params key=k item=v}
<input type="hidden" name="{$k}" value="{$v}" />
{/foreach}
</form>
{literal}
<script language="JavaScript">
    $(document).ready(function() {
        $("#confirmOrderDotpay").click(function() {
		  $("#dpForm").submit();
		});
    });


</script>
{/literal}

<p class="cart_navigation" style="display: block !important">
	<a href="{$link->getPageLink('order.php', true)}?step=3" class="button-exclusive btn btn-default"><i class="icon-chevron-left"></i>{l s='Other payment methods' mod='dotpay'}</a>
	<button style="" id="confirmOrderDotpay" type="button" class="button btn btn-default standard-checkout button-medium"><span>{l s='Confirm purchase' mod='dotpay'}</span></button>
</p>

{else}

<p class="dotpay_return"><img src="{$module_dir}img/Dotpay_logo_napis{if $lang_iso == 'pl'}_pl{else}_en{/if}.png" /><img width="128" height="128" src="{$module_dir}img/loading2.gif" /></p>
<p class="dotpay_return">{l s='Yours payment is loading. Please wait.' mod='dotpay'}</p>
<form action="{$form_url}" method="post" id="dpForm" name="dpForm" target="_parent">
{foreach from=$params key=k item=v}
<input type="hidden" name="{$k}" value="{$v}"/>
{/foreach}
</form>
{literal}
<script language="JavaScript">
setTimeout(function(){document.dpForm.submit()}, 2000);
</script>
{/literal}

{/if}
