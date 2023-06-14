<?php //>

namespace matrix\shopping\shipping;

class Ecpay {

    public static function applyCvsShipment($order) {
        $cfg = load_cfg('ecpay-shipping');

        $data = [
            'MerchantID' => $cfg['MerchantID'],
            'MerchantTradeNo' => $order['order_no'],
            'MerchantTradeDate' => date('Y/m/d H:i:s'),
            'LogisticsType' => 'CVS',
            'LogisticsSubType' => $order['shipment'],
            'GoodsAmount' => round($order['amount'] + $order['shipping']),
            'SenderName' => $cfg['SenderName'],
            'SenderPhone' => $cfg['SenderPhone'],
            'ReceiverName' => $order['name'],
            'ReceiverCellPhone' => $order['phone'],
            'ServerReplyURL' => get_url(APP_ROOT . 'api/ecpay-shipping/cvs-notify'),
            'ReceiverStoreID' => $order['store_id'],
        ];

        return self::request('Create', $data, $cfg);
    }

    private static function checksum($data, $key, $iv) {
        unset($data['CheckMacValue']);

        ksort($data);

        $sign = 'HashKey=' . $key;

        foreach ($data as $name => $value) {
            $sign = "{$sign}&{$name}={$value}";
        }

        $sign = strtolower(urlencode($sign . '&HashIV=' . $iv));

        $sign = str_replace('%20', '+', $sign);
        $sign = str_replace('%21', '!', $sign);
        $sign = str_replace('%28', '(', $sign);
        $sign = str_replace('%29', ')', $sign);
        $sign = str_replace('%2a', '*', $sign);
        $sign = str_replace('%2d', '-', $sign);
        $sign = str_replace('%2e', '.', $sign);
        $sign = str_replace('%5f', '_', $sign);

        return strtoupper(md5($sign));
    }

    private static function request($api, $data, $cfg) {
        $data['CheckMacValue'] = self::checksum($data, $cfg['HashKey'], $cfg['HashIV']);

        $context = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($data),
            ],
        ];

        $response = file_get_contents("{$cfg['url']}{$api}", false, stream_context_create($context));

        if ($response) {
            $tokens = explode('|', $response);

            if (@$tokens[0] === 1) {
                parse_str($tokens[1], $values);

                if (@$values['CheckMacValue'] === self::checksum($values, $cfg['HashKey'], $cfg['HashIV'])) {
                    return $values;
                }
            }

            return null;
        }

        return false;
    }

}
