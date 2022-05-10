<?php //>

namespace matrix\shopping\payment;

use matrix\web\Controller;

class SunpayAtmNotify extends Controller {

    public function available() {
        return ($this->method() === 'POST' && $this->name() === $this->path());
    }

    public function verify() {
        return true;
    }

    protected function process($form) {
        $data = $this->checksum(array_map('urldecode', $form));

        if ($data && $data['errcode'] === '00') {
            $model = model('Order');

            $info = explode('v', $data['Td']);
            $order = $model->find(['order_no' => $info[0]]);

            if ($order && $order['amount'] + $order['shipping'] == $data['MN']) {
                switch ($order['status']) {
                case 1:
                    $order['payment_notice'] = json_encode($form, JSON_UNESCAPED_UNICODE);
                    $order['pay_time'] = date(cfg('system.timestamp'));
                    $order['status'] = 2;
                    $order = $model->update($order);
                    if ($order) {
                        return $this->subprocess($form, ['success' => true, 'view' => 'payment/sunpay-ok.php', 'order' => $order]);
                    }
                    break;
                case 2:
                    return ['success' => true, 'view' => 'payment/sunpay-ok.php'];
                }
            }
        }

        return ['view' => 'empty.php'];
    }

    private function checksum($form) {
        logging($this->name())->info('CHECKSUM', $form);

        $sunpay = load_cfg('sunpay');

        $tokens = [
            $sunpay['atm'],
            $sunpay['password'],
            @$form['buysafeno'],
            @$form['MN'],
            @$form['errcode'],
            @$form['CargoNo'],
        ];

        if ($form['ChkValue'] === strtoupper(sha1(implode('', $tokens)))) {
            logging($this->name())->info('OK');

            return $form;
        }

        logging($this->name())->info('ERROR');

        return false;
    }

}
