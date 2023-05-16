<?php //>

namespace matrix\shopping\payment;

use matrix\web\Controller;

class NewebpayNotify extends Controller {

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

            if ($order && $order['status'] === 1 && $order['amount'] + $order['shipping'] == $data['Result']['Amt']) {
                switch ($data['Result']['PaymentType']) {
                case 'CREDIT':
                    $payment = "{$data['Result']['Card6No']}******{$data['Result']['Card4No']}";
                    break;
                default:
                    $payment = $order['payment'];
                }

                $order = $this->updateOrder($order['id'], [
                    'pay_time' => now(),
                    'payment' => $payment,
                    'payment_notice' => json_encode($form, JSON_UNESCAPED_UNICODE),
                    'status' => 2,
                ]);

                return $this->subprocess($form, [
                    'success' => true,
                    'view' => 'empty.php',
                    'order' => $order,
                ]);
            }
        }

        return ['view' => 'empty.php'];
    }

    protected function getOrder($order_no) {
        return table('Order')->filter(['order_no' => $order_no])->get();
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
