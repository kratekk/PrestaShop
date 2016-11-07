<?php

class AdminDotpayRefundController extends ModuleAdminController {
    private $config;
    
    public function __construct() {
        parent::__construct();
        $this->config = new DotpayConfig();
    }
    
    /**
     * Makes requesting a refund
     */
    public function display() {
        $sa = new DotpaySellerApi($this->config->getDotpaySellerApiUrl());
        $result = $sa->makeReturnMoney(
            $this->config->getDotpayApiUsername(),
            $this->config->getDotpayApiPassword(),
            Tools::getValue('payment'),
            Tools::getValue('amount'),
            Tools::getValue('order_id'),
            Tools::getValue('description')
        );
        
        if($result['http_code'] == 200) {
            $status = 'success';
            $state = $this->config->getDotpayWaitingRefundStatusId();
            $history = new OrderHistory();
            $history->id_order = Tools::getValue('order_id');
            $history->changeIdOrderState($state, $history->id_order);
            $history->addWithemail(true);
        } else {
            $status = 'error';
            $this->context->cookie->dotpay_error = $result['detail'];
        }
        Tools::redirectAdmin($this->getRedirectUrl($status));
    }
    
    /**
     * Confirms requesting a refund
     */
    public function displayAjax() {
        $order = new Order(Tools::getValue('order'));
        $payments = OrderPayment::getByOrderId(Tools::getValue('order'));
        $sumOfPayments = 0.0;
        foreach($payments as $payment) {
            if($payment->payment_method == $this->module->displayName)
                $sumOfPayments += (float)$payment->amount;
        }
        if(abs($sumOfPayments) < 0.01)
            $sumOfPayments = 0.0;
        $sa = new DotpaySellerApi($this->config->getDotpaySellerApiUrl());
        $payment = $sa->getPaymentByNumber(
            $this->config->getDotpayApiUsername(),
            $this->config->getDotpayApiPassword(),
            Tools::getValue('payment')
        );
        if(isset($payment->payment_method)) {
            $channel = $payment->payment_method->channel_id;
            unset($payment->payment_method);
            $payment->channel_id = $channel;
        }
        $payment->sum_of_payments = $sumOfPayments;
        $payment->return_description = $this->l('Refund of order:').' '.$order->reference;
        die(json_encode($payment));
    }
    
    /**
     * Returns redirect URL where shop administrator is returned after requesting a refund
     * @param string $status Status string
     * @return string
     */
    private function getRedirectUrl($status) {
        $pathInfo = parse_url($_SERVER['HTTP_REFERER']);
        $queryString = $pathInfo['query'];
        $queryArray = array();
        parse_str($queryString, $queryArray);
        $queryArray['dotpay_refund'] = $status;
        $newQueryStr = http_build_query($queryArray);
        return $pathInfo['scheme'].'://'.$pathInfo['host'].$pathInfo['path'].'?'.$newQueryStr;
    }
}

?>