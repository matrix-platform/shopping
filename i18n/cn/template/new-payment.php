<?php //>

return [

    'mailer' => 'gmail',

    'to' => null,

    'subject' => '付款完成',

    'content' => '订单号码: {{ order.order_no }}<br>金额: {{ order.amount + order.shipping }}',

];
