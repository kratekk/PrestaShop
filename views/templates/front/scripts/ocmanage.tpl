{literal}<script type="text/javascript" language="JavaScript">
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


$(document).ready(function(){
    $('.card-remove').click(function(){
        if(confirm(onRemoveMessage+' '+$(this).parents('tr').find('td:first').text()+'?')) {
            var cardId = $(this).data('id');
            $.ajax({
                "url":removeUrl,
                "method":"post",
                "data":{
                    "card_id":cardId
                }
            }).done(function(r){
                if(r=='OK')
                    alert(onDoneMessage);
                else {
                    console.warn(r);
                    alert(onFailureMessage);
                }
                location.href=location.href;
            }).fail(function(r){
                alert(onFailureMessage);
            });
        }
    });
});
</script>{/literal}