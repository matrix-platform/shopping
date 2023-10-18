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
            'GoodsName' => $cfg['GoodsName'],
            'SenderName' => $cfg['SenderName'],
            'SenderCellPhone' => $cfg['SenderCellPhone'],
            'ReceiverName' => $order['name'],
            'ReceiverCellPhone' => $order['phone'],
            'ServerReplyURL' => get_url(APP_ROOT . 'api/ecpay-shipping/cvs-notify'),
            'ReceiverStoreID' => $order['store_id'],
        ];

        $response = self::request('Express/Create', $data, $cfg);

        if ($response) {
            $tokens = preg_split('/\|/', $response, 2);

            if (@$tokens[0] === '1') {
                parse_str($tokens[1], $values);

                if (@$values['CheckMacValue'] === self::checksum($values, $cfg['HashKey'], $cfg['HashIV'])) {
                    return $values;
                }
            }

            return null;
        }

        return false;
    }

    public static function checksum($data, $key, $iv) {
        unset($data['CheckMacValue']);

        $names = array_keys($data);

        natcasesort($names);

        $sign = 'HashKey=' . $key;

        foreach ($names as $name) {
            $sign = "{$sign}&{$name}={$data[$name]}";
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

        return $response;
    }

}
