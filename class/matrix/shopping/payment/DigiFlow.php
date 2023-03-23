<?php //>

namespace matrix\shopping\payment;

use Carbon\Carbon;

class DigiFlow {

    public static function applyAtm($order, $member) {
        return self::apply($order, $member, 150);
    }

    public static function applyCreditCard($order, $member) {
        return self::apply($order, $member, 111);
    }

    public static function applyCvs($order, $member) {
        return self::apply($order, $member, 170);
    }

    public static function query($order_no) {
        $cfg = load_cfg('digiflow');

        $data = [
            'version' => $cfg['version'],
            'merchant_id' => $cfg['merchant_id'],
            'terminal_id' => $cfg['terminal_id'],
            'order_no' => $order_no,
            'timestamp' => time() * 1000,
        ];

        $data['sign'] = self::checksum($data, $cfg['key']);

        return self::request($cfg['query_url'], $data);
    }

    private static function apply($order, $member, $type) {
        $cfg = load_cfg('digiflow');

        $data = [
            'version' => $cfg['version'],
            'merchant_id' => $cfg['merchant_id'],
            'terminal_id' => $cfg['terminal_id'],
            'order_no' => "{$order['order_no']}v{$order['payment_ver']}",
            'currency' => $cfg['currency'],
            'order_desc' => $cfg['order_desc'],
            'order_amount' => ($order['amount'] + $order['shipping']) * 100,
            'expiry_time' => Carbon::today()->addDays(3)->endOfDay()->format('YmdHis'),
            'payment_type' => $type,
            'member_id' => $member['id'],
            'buyer_mail' => $member['mail'],
            'timestamp' => time() * 1000,
        ];

        $data['sign'] = self::checksum($data, $cfg['key']);

        $response = self::request($cfg['apply_url'], $data);

        if ($response) {
            $order['cashier'] = $response['payment_url'];
            $order['cashier_type'] = 'redirect';
            $order['payment_request'] = json_encode($response, JSON_UNESCAPED_UNICODE);
            $order['payment_response'] = null;

            return $order;
        }

        return $response;
    }

    private static function checksum($data, $key) {
        ksort($data);

        $tokens = [];

        foreach ($data as $name => $value) {
            if (strlen("{$value}")) {
                $tokens[] = "{$name}={$value}";
            }
        }

        $tokens[] = "key={$key}";

        return base64_encode(hash('sha256', implode('&', $tokens), true));
    }

    private static function request($url, $data) {
        $context = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
                'content' => http_build_query($data),
            ],
        ];

        $response = json_decode(file_get_contents($url, false, stream_context_create($context)), true);

        if ($response) {
            if (@$response['return_code'] === '000000') {
                return $response;
            }

            logging('digiflow')->info(json_encode($response, JSON_UNESCAPED_UNICODE));

            return null;
        }

        return false;
    }

}
