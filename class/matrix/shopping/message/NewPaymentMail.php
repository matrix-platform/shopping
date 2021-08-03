<?php //>

namespace matrix\shopping\message;

use matrix\utility\Fn;

trait NewPaymentMail {

    protected function postprocess($form, $result) {
        $this->mail($result['order']);

        return $result;
    }

    protected function mail($order) {
        $content = load_i18n('template/new-payment', $order['language']);

        if (@$content['to']) {
            $content['order'] = $order;

            Fn::send_mail(array_merge(load_cfg($content['mailer']), $content));
        }
    }

}
