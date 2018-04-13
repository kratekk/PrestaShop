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

function loadJS(filename) {
    var jsfile=document.createElement('script');
    jsfile.setAttribute("type","text/javascript");
    jsfile.setAttribute("src", filename);
    var target = document.getElementsByTagName("head")[0];
    target.insertBefore(jsfile, target.firstChild);
}

document.addEventListener("DOMContentLoaded", function(event) { 
    if(typeof jQuery === "undefined") {
        loadJS('https://code.jquery.com/jquery-2.2.4.min.js');
    }
});

</script>{/literal}
