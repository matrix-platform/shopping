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
            $model = model('Order');

            $info = explode('v', $data['Result']['MerchantOrderNo']);
            $order = $model->find(['order_no' => $info[0], 'status' => 1]);

            if ($order && $order['amount'] + $order['shipping'] == $data['Result']['Amt']) {
                $order['payment_notice'] = json_encode($form, JSON_UNESCAPED_UNICODE);
                $order['pay_time'] = date(cfg('system.timestamp'));
                $order['status'] = 2;

                switch ($data['Result']['PaymentType']) {
                case 'CREDIT':
                    $order['payment'] = "{$data['Result']['Card6No']}******{$data['Result']['Card4No']}";
                    break;
                }

                $order = $model->update($order);

                if ($order) {
                    return $this->subprocess($form, [
                        'success' => true,
                        'view' => 'empty.php',
                        'order' => $order,
                    ]);
                }
            }
        }

        return ['view' => 'empty.php'];
    }

    private function checksum($form) {
        logger($this->name())->info(json_encode($form, JSON_UNESCAPED_UNICODE));

        $args = load_cfg('newebpay');
        $data = json_decode(Newebpay::decrypt($form['TradeInfo'], $args['HashKey'], $args['HashIV']), true);

        logger($this->name())->info(json_encode($data, JSON_UNESCAPED_UNICODE));

        if (@$data['Status'] === 'SUCCESS') {
            logger($this->name())->info('OK');

            return $data;
        }

        logger($this->name())->info('ERROR');

        return false;
    }

}
