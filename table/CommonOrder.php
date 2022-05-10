<?php //>

use matrix\db\column\CreateTime;
use matrix\db\column\Double;
use matrix\db\column\FormNumber;
use matrix\db\column\Integer;
use matrix\db\column\Text;
use matrix\db\column\Textarea;
use matrix\db\column\Timestamp;
use matrix\db\Table;

$tbl = new Table('base_order');

$tbl->add('order_no', FormNumber::class)
    ->length(5)
    ->readonly(true)
    ->required(true)
    ->sequence('base_order_number')
    ->unique(true);

$tbl->add('member_id', Integer::class)
    ->associate('member', 'Member')
    ->readonly(true);

$tbl->add('amount', Double::class)
    ->readonly(true)
    ->required(true);

$tbl->add('shipping', Double::class)
    ->default(0)
    ->readonly(true)
    ->required(true);

$tbl->add('payment_method_id', Integer::class)
    ->associate('payment_method', 'PaymentMethod')
    ->readonly(true);

$tbl->add('payment', Text::class);

$tbl->add('payment_request', Text::class)
    ->invisible(true);

$tbl->add('payment_response', Text::class)
    ->invisible(true);

$tbl->add('payment_notice', Text::class)
    ->invisible(true);

$tbl->add('payment_ver', Integer::class)
    ->default(0)
    ->invisible(true)
    ->required(true);

$tbl->add('pay_time', Timestamp::class);

$tbl->add('drawback_time', Timestamp::class);

$tbl->add('invoice_num', Text::class);

$tbl->add('invoice_type', Integer::class)
    ->options(load_options('invoice-type'));

$tbl->add('tax_id', Text::class);

$tbl->add('invoice_title', Text::class);

$tbl->add('invoice_request', Text::class)
    ->invisible(true);

$tbl->add('invoice_response', Text::class)
    ->invisible(true);

$tbl->add('invoice_time', Timestamp::class);

$tbl->add('snapshot', Text::class)
    ->invisible(true)
    ->readonly(true);

$tbl->add('remark', Textarea::class);

$tbl->add('create_time', CreateTime::class);

$tbl->add('cancel_time', Timestamp::class);

$tbl->add('status', Integer::class)
    ->default(1)
    ->options(load_options('order-status'))
    ->required(true);

$tbl->ranking('-id');
$tbl->title('order_no');

return $tbl;
