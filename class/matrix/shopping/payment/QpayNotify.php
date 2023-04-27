<?php //>

namespace matrix\shopping\payment;

use matrix\web\Controller;

class QpayNotify extends Controller {

    public function available() {
        return ($this->method() === 'POST' && $this->name() === $this->path());
    }

    public function verify() {
        return true;
    }

    protected function process($form) {
        $response = Qpay::apply('OrderPayQuery', load_cfg('qpay'), $form);

        if ($response) {
            if ($response['Status'] === 'S') {
                $data = $response['TSResultContent'];
                $response = json_encode($response, JSON_UNESCAPED_UNICODE);

                logging($this->name())->info($response);

                if ($data['Status'] === 'S' && $data['APType'] === 'PayOut') {
                    $info = explode('v', $data['OrderNo']);
                    $amount = round(intval($data['Amount']) / 100.0, 2);

                    $model = model('Order');
                    $order = $model->find(['order_no' => $info[0], 'status' => [1, 2]]);

                    if ($order && ($order['amount'] + $order['shipping']) == $amount) {
                        if ($order['status'] === 1) {
                            $order['payment_notice'] = $response;
                            $order['pay_time'] = date(cfg('system.timestamp'));
                            $order['status'] = 2;

                            switch ($data['PayType']) {
                            case 'C':
                                $order['payment'] = "{$data['LeftCCNo']}******{$data['RightCCNo']}";
                                break;
                            }

                            $order = $model->update($order);

                            $this->data($order);
                        }

                        if ($order) {
                            return $this->subprocess($form, [
                                'success' => true,
                                'view' => 'payment/qpay-ok.php',
                            ]);
                        }
                    }
                }
            }
        }

        return ['view' => 'empty.php'];
    }

}
