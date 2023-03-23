<?php //>

namespace matrix\shopping\payment;

use matrix\web\Controller;

class DigiFlowReturn extends Controller {

    public function available() {
        return ($this->method() === 'POST' && $this->name() === $this->path());
    }

    public function verify() {
        return true;
    }

    protected function process($form) {
        $info = explode('v', @$form['order_no']);

        if ($info) {
            $order = model('Order')->find(['order_no' => $info[0]]);

            if ($order) {
                $this->data($order);

                return [
                    'success' => true,
                    'view' => '302.php',
                    'path' => $this->getOrderPath($order),
                ];
            }
        }

        return ['view' => '404.php'];
    }

    protected function getOrderPath($order) {
        return get_url(APP_ROOT . 'order/' . $order['id']);
    }

}
