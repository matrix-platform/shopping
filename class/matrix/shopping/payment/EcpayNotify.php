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
            $model = model('Order');

            $info = explode('v', $data['MerchantTradeNo']);
            $order = $model->find(['order_no' => $info[0], 'status' => 1]);

            if ($order && $order['amount'] + $order['shipping'] == $data['TradeAmt']) {
                $order['payment_notice'] = json_encode($form);
                $order['pay_time'] = date(cfg('system.timestamp'));
                $order['status'] = 2;

                $order = $model->update($order);

                if ($order) {
                    return $this->subprocess($form, [
                        'success' => true,
                        'view' => 'payment/ecpay-ok.php',
                        'order' => $order,
                    ]);
                }
            }
        }

        return ['view' => 'empty.php'];
    }

    private function checksum($form) {
        logger($this->name())->info(json_encode($form));

        $ecpay = load_cfg('ecpay');

        if ($form['CheckMacValue'] === Ecpay::checksum($form, @$ecpay['HashKey'], @$ecpay['HashIV'])) {
            if ($form['RtnCode'] === '1') {
                logger($this->name())->info('OK');
                return $form;
            }
        }

        logger($this->name())->info('ERROR');

        return false;
    }

}
