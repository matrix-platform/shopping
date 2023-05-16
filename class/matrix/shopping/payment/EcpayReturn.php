<?php //>

namespace matrix\shopping\payment;

use matrix\web\Controller;

class EcpayReturn extends Controller {

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
            $order = $this->getOrder(strstr($data['MerchantTradeNo'], 'v', true));

            if ($order) {
                switch ($this->paymentMethod()) {
                case 'ATM':
                    $payment = "{$data['BankCode']}-{$data['vAccount']}";
                    break;
                case 'CVS':
                    $payment = "{$data['PaymentNo']}";
                    break;
                default:
                    $payment = $order['payment'];
                }

                $order = $this->updateOrder($order['id'], [
                    'payment' => $payment,
                    'payment_response' => json_encode($data, JSON_UNESCAPED_UNICODE),
                ]);

                $this->data($order);

                return [
                    'success' => true,
                    'view' => '302.php',
                    'path' => $this->getOrderPath($order),
                ];
            }
        }
    }

    protected function getOrder($order_no) {
        return table('Order')->filter(['order_no' => $order_no])->get();
    }

    protected function getOrderPath($order) {
        return get_url(APP_ROOT . 'order/' . $order['id']);
    }

    protected function updateOrder($id, $data) {
        return table('Order')->filter($id)->updateOne($data);
    }

    private function checksum($form) {
        logging($this->name())->info(json_encode($form, JSON_UNESCAPED_UNICODE));

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
                logging($this->name())->info('OK');
                return $form;
            }
        }

        logging($this->name())->info('ERROR');

        return false;
    }

}
