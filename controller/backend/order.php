<?php //>

return new class('Order') extends matrix\web\backend\ListController {

    protected function init() {
        $table = $this->table();

        $table->add('username', 'member.username');
        $table->add('name', 'member.name');
        $table->add('payment_method_title', 'payment_method.title');

        $this->columns([
            'order_no',
            'username',
            'name',
            'amount',
            'payment_method_title',
            'pay_time',
            'status',
        ]);
    }

};
