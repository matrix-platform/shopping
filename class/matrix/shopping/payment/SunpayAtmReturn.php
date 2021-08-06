<?php //>

namespace matrix\shopping\payment;

use matrix\web\Controller;

class SunpayAtmReturn extends Controller {

    public function available() {
        return ($this->method() === 'POST' && $this->name() === $this->path());
    }

    public function verify() {
        return true;
    }

    protected function process($form) {
        $data = $this->checksum(array_map('urldecode', $form));

        if ($data) {
            $model = model('Order');

            $info = explode('v', $data['Td']);
            $order = $model->find(['order_no' => $info[0], 'status' => 1]);

            if ($order && !$order['payment']) {
                $order['payment'] = "{$data['BankName']}({$data['BankCode']})-{$data['EntityATM']}";
                $order['payment_response'] = json_encode($data, JSON_UNESCAPED_UNICODE);

                $order = $model->update($order);
            }

            if ($order) {
                if (@$form['SendType'] === '2') {
                    return ['success' => true, 'view' => '302.php', 'path' => url(APP_ROOT . 'order/' . $order['id'])];
                } else {
                    return ['success' => true, 'view' => 'payment/sunpay-ok.php'];
                }
            }
        }
    }

    private function checksum($form) {
        logger($this->name())->info('CHECKSUM', $form);

        $sunpay = load_cfg('sunpay');

        $tokens = [
            $sunpay['atm'],
            $sunpay['password'],
            @$form['buysafeno'],
            @$form['MN'],
            @$form['EntityATM'],
        ];

        if ($form['ChkValue'] === strtoupper(sha1(implode('', $tokens)))) {
            logger($this->name())->info('OK');

            return $form;
        }

        logger($this->name())->info('ERROR');

        return false;
    }

}
