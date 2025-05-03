<?php //>

namespace matrix\shopping\payment;

class Chb {

    public static function applyAtm($order) {
        $merchant = strval(cfg('chb.merchant_id'));
        $id = strval($order['id']);
        $amount = str_pad($order['amount'], 8, '0', STR_PAD_LEFT);

        $data = "{$merchant}{$id}{$amount}";
        $nums = '731731731731731731731';
        $sum = 0;

        for ($i = 0; $i < 21; $i++) {
            $sum += $data[$i] * $nums[$i];
        }

        $checksum = $sum % 10;

        if ($checksum) {
            $checksum = 10 - $checksum;
        }

        $order['payment'] = "{$merchant}{$id}{$checksum}{$amount}";

        return $order;
    }

}
