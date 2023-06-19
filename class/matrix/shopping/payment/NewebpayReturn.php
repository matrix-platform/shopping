<?php //>

namespace matrix\shopping\payment;

use matrix\web\Controller;

class NewebpayReturn extends Controller {

    public function available() {
        return ($this->method() === 'POST' && $this->name() === $this->path());
    }

    public function verify() {
        return true;
    }

    protected function process($form) {
        $data = $this->checksum($form);

        if ($data) {
            $order = $this->getOrder(strstr($data['Result']['MerchantOrderNo'], 'v', true));

            if ($order) {
                switch ($data['Result']['PaymentType']) {
                case 'VACC':
                    $payment = "{$data['Result']['BankCode']}-{$data['Result']['CodeNo']}";
                    break;
                case 'CVS':
                    $payment = "{$data['Result']['CodeNo']}";
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

        $args = load_cfg('newebpay');
        $data = json_decode(Newebpay::decrypt($form['TradeInfo'], $args['HashKey'], $args['HashIV']), true);

        logging($this->name())->info(json_encode($data, JSON_UNESCAPED_UNICODE));

        if (@$data['Status'] === 'SUCCESS') {
            logging($this->name())->info('OK');

            return $data;
        }

        logging($this->name())->info('ERROR');

        return false;
    }

}
