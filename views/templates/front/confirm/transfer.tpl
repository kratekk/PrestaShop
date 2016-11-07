<section id="instruction">
    <div class="row">
        <div class="col-xs-12">
            <p id="instruction-content">{l s='In order to pay login to your bank system and make a money transfer, using the following data:' mod='dotpay'}</p>
        </div>
        <div class="col-md-4 col-md-offset-1">
            <label>
                {l s='Account number' mod='dotpay'}
                <input type="text" class="important" id="iban" value="{$bankAccount}" />
            </label>
            <label>
                {l s='Amount of payment' mod='dotpay'}
                <div class="input-group">
                    <input type="text" class="important" id="amount" value="{$amount}" aria-describedby="transfer-currency">
                    <span class="input-group-addon" id="transfer-currency">{$currency}</span>
                </div>
            </label>
            <label>
                {l s='Title of payment' mod='dotpay'}
                <input type="text" class="important" id="payment-title" value="{$title}" />
            </label>
        </div>
        <div class="col-md-4 col-md-offset-2">
            <label>
                {l s='Name of recipient' mod='dotpay'}
                <input type="text" class="important" id="recipient" value="{$recipient}" />
            </label>
            <label>
                {l s='Street' mod='dotpay'}
                <input type="text" class="important" id="street" value="{$street}" />
            </label>
            <label>
                {l s='Post code and city' mod='dotpay'}
                <input type="text" class="important" id="post-code-city" value="{$city}" />
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

