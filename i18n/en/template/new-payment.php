<?php //>

return [

    'mailer' => 'gmail',

    'to' => null,

    'subject' => 'Payment completed',

    'content' => 'Order No: {{ order.order_no }}<br>Amount: {{ order.amount + order.shipping }}',

];
