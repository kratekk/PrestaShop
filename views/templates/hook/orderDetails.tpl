<style>
    #dotpayDetailsPaymentPanel form {
        max-width: 600px;
    }
    .dotpay-margin {
        margin: 5px;
    }
    .dotpay-return-param {
        width: 500px !important;
    }
</style>
<script>{literal}
    function updateDotpayReturnDetails(payment) {
        $.post(
            "{/literal}{$returnUrl}{literal}&ajax=1",
            {"payment": payment, "order": {/literal}{$orderId}{literal}},
            function(response) {
                var payment = JSON.parse(response);
                var form = $('#dotpayDetailsPaymentPanel form');
                form.find('input[name=amount]').val(payment.sum_of_payments).data('maxvalue', payment.sum_of_payments);
                form.find('#return-currency').html(payment.currency);
                form.find('input[name=description]').val(payment.return_description);
                if(payment.sum_of_payments == 0.0)
                    form.find('input[type=submit]').attr('disabled', true);
                else
                    form.find('input[type=submit]').attr('disabled', false);
            }
        );
    }
    $(document).ready(function(){
        $('#dotpay-return-payment').change(function(){
            updateDotpayReturnDetails($(this).val());
        });
        $('.dotpay-return-amount').change(function(){
            var obj = $(this);
            if(obj.val()==='')
                return true;
            var value = parseFloat(obj.val().replace(',','.'));
            if(value > parseFloat(obj.data('maxvalue')))
                obj.val(obj.data('maxvalue'));
            else if(value <= 0.0)
                obj.val('0.01');
        });
        updateDotpayReturnDetails($('#dotpay-return-payment').val());
    });
{/literal}</script>
<div id="dotpayDetailsPaymentPanel" class="panel">
    <div class="panel-heading">
        <i class="icon-university"></i>
        Płatności Dotpay<span class="badge">$</span>
    </div>
    <h4>Zwrot płatności</h4>
    <form method="POST" action="{$returnUrl}">
        <input type="hidden" name="order_id" value="{$orderId}" />
        <select id="dotpay-return-payment" name="payment" class="dotpay-margin dotpay-return-param">
            {foreach from=$payments key=count item=payment}
                <option value="{$payment->transaction_id}">{$payment->transaction_id}</option>
            {/foreach}
        </select>
        <div class="input-group dotpay-margin dotpay-return-param">
            <input type="number" name="amount" class="form-control dotpay-return-amount" aria-describedby="return-currency" step="0.01" value="" data-maxvalue="0" />
            <span class="input-group-addon" value="" id="return-currency"></span>
        </div>
        <input class="dotpay-margin dotpay-return-param" size="60" type="text" name="description" value="" />
        <input class="dotpay-margin" type="submit" value="Wykonaj zwrot" />
    </form>
</div>