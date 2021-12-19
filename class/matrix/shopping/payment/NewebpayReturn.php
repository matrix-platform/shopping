<?php //>

namespace matrix\shopping\payment;

use matrix\web\Controller;

class NewebpayReturn extends Controller {

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
            $order = $model->find(['order_no' => $info[0]]);
            $order['payment_response'] = json_encode($data, JSON_UNESCAPED_UNICODE);

            switch ($data['Result']['PaymentType']) {
            case 'VACC':
                $order['payment'] = "{$data['Result']['BankCode']}-{$data['Result']['CodeNo']}";
                break;
            case 'CVS':
                $order['payment'] = "{$data['Result']['CodeNo']}";
                break;
            }

            $order = $model->update($order);

            if ($order) {
                $this->data($order);

                return [
                    'success' => true,
                    'view' => '302.php',
                    'path' => url(APP_ROOT . 'order/' . $order['id']),
                ];
            }
        }
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
