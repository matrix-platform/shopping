<?php //>

namespace matrix\shopping\payment;

use matrix\web\Controller;

class SunpayCreditCardNotify extends Controller {

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
            $order = $model->find(['order_no' => $info[0]]);
            $update = false;

            if ($order && $data['errcode'] === '00' && $order['status'] === 1 && $order['amount'] + $order['shipping'] == $data['MN']) {
                $order['payment'] = $data['Card_NO'] ? "****-****-****-{$data['Card_NO']}" : null;
                $order['payment_notice'] = json_encode($form, JSON_UNESCAPED_UNICODE);
                $order['pay_time'] = date(cfg('system.timestamp'));
                $order['status'] = 2;

                $order = $model->update($order);
                $update = true;
            }

            if ($order) {
                if (@$form['SendType'] === '2') {
                    $result = ['success' => true, 'view' => '302.php', 'path' => get_url(APP_ROOT . 'order/' . $order['id'])];
                } else {
                    $result = ['success' => true, 'view' => 'payment/sunpay-ok.php'];
                }

                if ($update) {
                    $result['order'] = $order;
                }

                return $this->subprocess($form, $result);
            }
        }
    }

    private function checksum($form) {
        logging($this->name())->info('CHECKSUM', $form);

        $sunpay = load_cfg('sunpay');

        $tokens = [
            $sunpay['credit-card'],
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
