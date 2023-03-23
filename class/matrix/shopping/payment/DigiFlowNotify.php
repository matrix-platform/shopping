<?php //>

namespace matrix\shopping\payment;

use matrix\web\Controller;

class DigiFlowNotify extends Controller {

    public function available() {
        return ($this->method() === 'POST' && $this->name() === $this->path());
    }

    public function verify() {
        return true;
    }

    protected function process($form) {
        $info = explode('v', @$form['order_no']);

        if ($info) {
            $order = model('Order')->find(['order_no' => $info[0], 'status' => 1]);

            if ($order) {
                $response = DigiFlow::query($form['order_no']);

                if (@$response['order_status'] == 1 && $order['amount'] + $order['shipping'] == $response['order_amount'] / 100) {
                    $order['payment_notice'] = json_encode($response, JSON_UNESCAPED_UNICODE);
                    $order['pay_time'] = date(cfg('system.timestamp'));
                    $order['status'] = 2;

                    switch ($response['payment_type']) {
                    case 111: //信用卡
                        $order['payment'] = "**** **** **** {$response['payment_info']['card_no']}";
                        break;
                    }

                    $order = model('Order')->update($order);

                    if ($order) {
                        return $this->subprocess($form, [
                            'success' => true,
                            'view' => 'empty.php',
                            'order' => $order,
                        ]);
                    }
                }
            }
        }

        return ['view' => '404.php'];
    }

}
