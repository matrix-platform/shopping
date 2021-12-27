<?php //>

return [

    'orders' => ['icon' => 'fas fa-shopping-cart', 'ranking' => 2500, 'parent' => null],

        'payment-method' => ['icon' => 'fas fa-cash-register', 'ranking' => 100, 'parent' => 'orders', 'group' => true, 'tag' => 'query'],

            'payment-method/' => ['parent' => 'payment-method', 'tag' => 'query'],

            'payment-method/delete' => ['parent' => 'payment-method', 'tag' => 'system'],

            'payment-method/insert' => ['parent' => 'payment-method', 'tag' => 'system'],

            'payment-method/new' => ['parent' => 'payment-method', 'tag' => 'system'],

            'payment-method/update' => ['parent' => 'payment-method', 'tag' => 'update'],

        'order' => ['icon' => 'far fa-list-alt', 'ranking' => 200, 'parent' => 'orders', 'group' => true, 'tag' => 'query'],

            'order/' => ['parent' => 'order', 'tag' => 'query'],

            'order/delete' => ['parent' => 'order', 'tag' => 'delete'],

            'order/update' => ['parent' => 'order', 'tag' => 'update'],

];
