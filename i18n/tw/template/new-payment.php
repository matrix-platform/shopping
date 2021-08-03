<?php //>

return [

    'mailer' => 'gmail',

    'to' => null,

    'subject' => '付款完成',

    'content' => '訂單號碼: {{ order.order_no }}<br>金額: {{ order.amount + order.shipping }}',

];
