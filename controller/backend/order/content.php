<?php //>

return new class('Order') extends matrix\web\backend\GetController {

    protected function init() {
        $table = $this->table();

        $table->add('username', 'member.username');
        $table->add('name', 'member.name');
        $table->add('payment_method_title', 'payment_method.title');

        $table->payment->readonly(true);
        $table->status->readonly(true);

        $this->columns([
            'order_no',
            'username',
            'name',
            'amount',
            'payment_method_title',
            'payment',
            'pay_time',
            'drawback_time',
            'remark',
            'create_time',
            'cancel_time',
            'status',
        ]);
    }

    protected function postprocess($form, $result) {
        $data = $result['data'];
        $table = $this->table();

        switch ($data['status']) {
        case 1:
            $table->drawback_time->invisible(true);
            break;
        case 2:
            $table->cancel_time->invisible(true);
            break;
        case 3:
            $table->pay_time->readonly(true);
            $table->cancel_time->invisible(true);
            break;
        case 9:
            $table->pay_time->invisible(true);
            $table->drawback_time->invisible(true);
            break;
        }

        return $result;
    }

};
