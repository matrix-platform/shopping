<?php //>

namespace matrix\shopping\payment;

use matrix\web\Controller;

class ChbNotify extends Controller {

    public function available() {
        return ($this->method() === 'POST' && $this->name() === $this->path());
    }

    public function verify() {
        return true;
    }

    protected function process($form) {
        $data = $this->decode($form);

        if ($data && REMOTE_ADDR === cfg('chb.ip')) {
            $order = $this->getOrder($data['INACCTNO']);

            if ($order && $order['amount'] + $order['shipping'] == $data['AMT']) {
                $order = $this->updateOrder($order['id'], [
                    'pay_time' => now(),
                    'payment_notice' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    'cancel_time' => null,
                    'status' => 2,
                ]);

                return $this->subprocess($form, [
                    'success' => true,
                    'view' => 'payment/chb-ok.php',
                    'order' => $order,
                ]);
            }
        }

        return ['view' => 'empty.php'];
    }

    protected function getOrder($payment) {
        return table('Order')->filter(['payment' => $payment, 'status' => 1])->get();
    }

    protected function updateOrder($id, $data) {
        return table('Order')->filter($id)->updateOne($data);
    }

    private function decode($form) {
        logging($this->name())->info(json_encode($form, JSON_UNESCAPED_UNICODE));

        $result = base64_decode(urldecode($form['result']));
        $cipher = 'DES-EDE3-CBC';
        $key = cfg('chb.key');
        $iv = cfg('chb.iv');

        $text = openssl_decrypt($result, $cipher, $key, OPENSSL_RAW_DATA, $iv);

        parse_str(trim(rtrim($text, "\0")), $data);

        logging($this->name())->info(json_encode($data, JSON_UNESCAPED_UNICODE));

        return $data;
    }

}
