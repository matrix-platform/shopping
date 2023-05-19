<?php //>

namespace matrix\shopping\payment;

use matrix\web\Controller;

class LinepayReturn extends Controller {

    protected function process($form) {
        $order = $this->getOrder(strstr(@$form['orderId'], 'v', true));

        if ($order && $order['status'] === 1) {
            $transaction = @$form['transactionId'];

            $linepay = load_cfg('linepay');

            $param = [
                'amount' => $order['amount'] + $order['shipping'],
                'currency' => $linepay['currency'],
            ];

            $response = Linepay::request("/v3/payments/{$transaction}/confirm", $linepay, $param);

            if ($response && $response['returnCode'] === '0000' && $response['info']['orderId'] === $form['orderId']) {
                $order = $this->updateOrder($order['id'], [
                    'pay_time' => now(),
                    'payment_response' => json_encode($form, JSON_UNESCAPED_UNICODE),
                    'payment_notice' => json_encode($response, JSON_UNESCAPED_UNICODE),
                    'status' => 2,
                ]);

                $this->data($order);

                return [
                    'success' => true,
                    'view' => '302.php',
                    'path' => $this->getOrderPath($order),
                ];
            }
        }

        return ['view' => 'empty.php'];
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

}
