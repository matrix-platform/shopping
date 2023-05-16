<?php //>

namespace matrix\shopping\payment;

use matrix\web\Controller;

class EcpayNotify extends Controller {

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

            if ($order && $order['status'] === 1 && $order['amount'] + $order['shipping'] == $data['TradeAmt']) {
                $order = $this->updateOrder($order['id'], [
                    'pay_time' => now(),
                    'payment_notice' => json_encode($form, JSON_UNESCAPED_UNICODE),
                    'status' => 2,
                ]);

                return $this->subprocess($form, [
                    'success' => true,
                    'view' => 'payment/ecpay-ok.php',
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

        $ecpay = load_cfg('ecpay');

        if ($form['CheckMacValue'] === Ecpay::checksum($form, @$ecpay['HashKey'], @$ecpay['HashIV'])) {
            if ($form['RtnCode'] === '1') {
                logging($this->name())->info('OK');
                return $form;
            }
        }

        logging($this->name())->info('ERROR');

        return false;
    }

}
