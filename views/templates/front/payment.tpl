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
*  @last modified: Dotpay: 2015-03-03 by PW
*   
*
*}

{if $DP_CHANNELS_VIEW_MAIN == 2}
{literal}
<style type="text/css">
#channel_list .my-widget-class{
    font-size: 1.7em !important;
	color: #881920;
	background-color: rgb(246, 246, 246);
	margin-left: 20px;
}
</style>
{/literal}
{/if}


{if $DP_CHANNELS_VIEW_MAIN == 1 || $DP_CHANNELS_VIEW_MAIN == 2 }
<form action="{$form_url_redirect_MAIN}" method="post" id="dpForm" name="dpForm" target="_parent" > 
<input type="hidden" name="URLDOTPAY" value="{$form_url_MAIN}"/>	
{else}
<form action="{$form_url_MAIN}" method="post" id="dpForm" name="dpForm" target="_parent" >     
{/if}


{if $dPorder_summary_MAIN != '1' || $DP_CHANNELS_VIEW_MAIN == 1 || $DP_CHANNELS_VIEW_MAIN == 2 }

<div class="row">
	<div class="col-xs-12">
		<p class="payment_module">
		
			<div class="box" id ="payment_dotpay_selected" style="overflow: auto;  border: 1px solid #D6D4D4; padding: 14px 18px 13px; margin: 0px 0px 30px; line-height: 23px;">
				<div class="row">
					<div class="col-sm-6">
						<a href="http://www.dotpay.pl/" target="_blank" title="Dotpay.pl"><img src="{$module_dir_OC_MAIN}img/Dotpay_logo_napis{if $lang_iso == 'pl'}_pl{else}_en{/if}.png" width="250px" border="0" /></a>
					 </div>	
					 <div class="col-sm-6">
						<h4>{l s='Pay by dotpay' mod='dotpay'}&nbsp;<em>{l s='(fast and secure internet payment)' mod='dotpay'}</em></h4>

						<h3>{l s='Order number ' mod='dotpay'}<span style="color: #881920;">#{$numer_zam}</span><br>{l s=' of ' mod='dotpay'}<span style="color: #881920;">{$paramsonechannel_MAIN['amount']} {$paramsonechannel_MAIN['currency']}</span></h3>
					 </div>
				</div>
                {if $DP_CHANNELS_VIEW_MAIN == 1 || $DP_CHANNELS_VIEW_MAIN == 2}
                <div class="row" id="channel_list" >
					<hr />
                    <h4>{l s='Select your payment channel ' mod='dotpay'}<i class="caret"></i></h4>
						   <link href="https://ssl.dotpay.pl/{if $DP_TEST_MAIN == 1}test_payment{else}t2{/if}/widget/payment_widget.min.css" rel="stylesheet">
							<script id="dotpay-payment-script" src="https://ssl.dotpay.pl/{if $DP_TEST_MAIN == 1}test_payment{else}t2{/if}/widget/payment_widget.js"></script>
							<script type="text/javascript">
							
								var dotpayWidgetConfig = { 
								  sellerAccountId: '{$paramsonechannel_MAIN['id']}',
								  amount: '{$paramsonechannel_MAIN['amount']}',
								  currency: '{$paramsonechannel_MAIN['currency']}',
								  lang: '{$paramsonechannel_MAIN['lang']}',
								  widgetFormContainerClass: 'dotpay-widget-container',
								  channelsWrapperContainerClass: 'my-channels-wrapper',
								  widgetClass: 'my-widget-class',
								  selectedWidgets: ['{if $DP_CHANNELS_VIEW_MAIN == 1}formWidget{else}selectWidget{/if}'],
								  offlineChannel: 'mark',
								  offlineChannelTooltip: true,
								  debug: false
								}
								
							</script>					
					
                    <div class="dotpay-widget-container"></div>
                </div>
                {/if}
				 <hr>
				<strong>{l s='Select the "Confirm purchase" to confirm your order and go to dotpay.pl where you can complete your payment.' mod='dotpay'}</strong>
				{if $DP_CHANNELS_VIEW_MAIN == 1 || $DP_CHANNELS_VIEW_MAIN == 2}
                <br /><br />
				<div class="box" style="background: rgba(238, 238, 238, 0.66) none repeat scroll 0% 0%;">
						<div class="checkbox" >
							<input type="checkbox" name="bylaw"  value="1"  id="agreement"  required = "required" checked = "checked">
							<label for="bylaw"><small>{$DP_AGREEMENT_BYLAW_MAIN}</small></label>
						</div>
						<div class="checkbox" >
							<input type="checkbox" name="personal_data" value="1" id="agreement" required = "required" checked = "checked">	
							<label for="personal_data"><small>{$DP_AGREEMENT_PERSONAL_DATA_MAIN}</small></label>
						</div>
                </div>
                {/if}

			</div>
		</p>
    </div>
</div>

{foreach from=$paramsonechannel_MAIN key=k item=v}
<input type="hidden" name="{$k}" value="{$v}" />
{/foreach}
</form>

{if $DP_CHANNELS_VIEW_MAIN == 1}
 		{literal}
		<script language="JavaScript">
		
				$(document).ready(function() {
					$("div.dotpay-widget-container ").on('change click',function() {
					var $mySelection = $("input[name='channel'][type='radio']:checked");
	
					if($mySelection.length > 0){
						//alert("checked" +$mySelection.val());
						$('html, body').animate({scrollTop: $("#payment_dotpay_selected").offset().top+800 }, 500);
					}else{
						//alert("not checked");
						return false;						
					}
				});
			});
			
				$(document).ready(function() {
					var kom1 = "{/literal}{l s=' Attention!' mod='dotpay'}\n\n {l s='Acceptance of the Terms and personal data IS REQUIRED.' mod='dotpay'}\n\n{literal}";
			
					$("#confirmOrderDotpay").click(function() {
					var $mySelection2 = $("input[name='channel'][type='radio']:checked");
					if($mySelection2.length > 0){
							if (($("input[name='bylaw']:checked").length)<=0 || ($("input[name='personal_data']:checked").length)<=0) {
									alert(kom1);
									return false;
								}else{
									$("#dpForm").submit();
								}
								   
						} else {
								   alert("{/literal}{l s=' Attention!' mod='dotpay'}\n\n {l s='Please select any payment channel !' mod='dotpay'}\n\n{literal}"); 
								   return false;
								  }				  
				});
			});
			
			
	</script>
		{/literal}
{/if}	
{if $DP_CHANNELS_VIEW_MAIN == 2}
	{literal}
		<script language="JavaScript">	
			$(document).ready(function() {
				var kom1 = "{/literal}{l s=' Attention!' mod='dotpay'}\n\n {l s='Acceptance of the Terms and personal data IS REQUIRED.' mod='dotpay'}\n\n{literal}";
			
				$("#confirmOrderDotpay").click(function() {
							if (($("input[name='bylaw']:checked").length)<=0 || ($("input[name='personal_data']:checked").length)<=0) {
									alert(kom1);
									return false;
								}else{
									$("#dpForm").submit();
								}
			  
				});
			});

		</script>
	{/literal}
{/if}	

<p class="cart_navigation" style="display: block !important">
	<a href="{$link->getPageLink('order.php', true)}?step=3" class="button-exclusive btn btn-default"><i class="icon-chevron-left"></i>{l s='Other payment methods' mod='dotpay'}</a>
	<button style="" id="confirmOrderDotpay" name="confirmOrderDotpay" type="button" class="button btn btn-default standard-checkout button-medium"><span>{l s='Confirm purchase' mod='dotpay'}</span></button>
</p>

{else}

<p class="dotpay_return"><img src="{$module_dir_OC_MAIN}img/Dotpay_logo_napis{if $lang_iso == 'pl'}_pl{else}_en{/if}.png" /><img width="128" height="128" src="{$module_dir_OC_MAIN}img/loading2.gif" /></p>
<p class="dotpay_return">{l s='Yours payment is loading. Please wait.' mod='dotpay'}</p>
<form action="{$form_url_MAIN}" method="post" id="dpForm" name="dpForm" target="_parent">
{foreach from=$paramsonechannel_MAIN key=k item=v}
<input type="hidden" name="{$k}" value="{$v}"/>
{/foreach}
</form>
{literal}
<script language="JavaScript">
setTimeout(function(){document.dpForm.submit()}, 3000);
</script>
{/literal}

{/if}
