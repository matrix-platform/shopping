<?php //>

namespace matrix\shopping\payment;

use matrix\web\Controller;

class EcpaySuccess extends Controller {

    public function __construct($paymentMethod) {
        $this->values = ['paymentMethod' => $paymentMethod];
    }

    public function available() {
        return ($this->method() === 'POST' && $this->name() === $this->path());
    }

    public function verify() {
        return true;
    }

    protected function process($form) {
        $data = $this->checksum($form);

        if ($data) {
            $model = model('Order');

            $info = explode('v', $data['MerchantTradeNo']);
            $order = $model->find(['order_no' => $info[0]]);
            $order['payment_response'] = json_encode($data, JSON_UNESCAPED_UNICODE);

            switch ($this->paymentMethod()) {
            case 'ATM':
                $order['payment'] = "{$data['BankCode']}-{$data['vAccount']}";
                break;
            case 'CVS':
                $order['payment'] = "{$data['PaymentNo']}";
                break;
            }

            $order = $model->update($order);

            if ($order) {
                return [
                    'success' => true,
                    'view' => '302.php',
                    'path' => url(APP_ROOT . 'order/' . $order['id']),
                ];
            }
        }
    }

    private function checksum($form) {
        logger($this->name())->info(json_encode($form));

        $ecpay = load_cfg('ecpay');

        if ($form['CheckMacValue'] === Ecpay::checksum($form, @$ecpay['HashKey'], @$ecpay['HashIV'])) {
            switch ($this->paymentMethod()) {
            case 'ATM':
                $code = '2';
                break;
            case 'Credit':
                $code = '1';
                break;
            case 'CVS':
                $code = '10100073';
                break;
            default:
                $code = null;
            }

            if ($code && $code === $form['RtnCode']) {
                logger($this->name())->info('OK');
                return $form;
            }
        }

        logger($this->name())->info('ERROR');

        return false;
    }

}
