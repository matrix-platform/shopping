<?php //>

namespace matrix\shopping\payment;

class Qpay {

    public static function apply($service, $cfg, $args) {
        $response = self::request($cfg['NonceURL'], ['ShopNo' => $cfg['ShopNo']]);

        if (is_array($response)) {
            $nonce = $response['Nonce'];

            $key = self::xor($cfg['A1'], $cfg['A2']) . self::xor($cfg['B1'], $cfg['B2']);
            $iv = self::getIV($nonce);

            $response = self::request($cfg['URL'], [
                'Version' => $cfg['Version'],
                'ShopNo' => $cfg['ShopNo'],
                'APIService' => $service,
                'Nonce' => $nonce,
                'Sign' => self::getSign($args, $nonce, $key),
                'Message' => self::encrypt($args, $key, $iv),
            ]);

            if (is_array($response)) {
                return self::decrypt($response['Message'], $key, self::getIV($response['Nonce']));
            }
        }

        return null;
    }

    public static function applyCreditCard($order, $member, $options = null) {
        $qpay = load_cfg('qpay');

        $param = [
            'AutoBilling' => 'Y', //自動請款
        ];

        if (@$options['regular']) {
            $param['PayTypeSub'] = 'REGULAR';
            $param['DeductTotalNum'] = 999;
            $param['PeriodType'] = 'M'; //月
            $param['DeductFreq'] = 1;
        }

        $response = self::apply('OrderCreate', $qpay, [
            'ShopNo' => $qpay['ShopNo'],
            'OrderNo' => "{$order['order_no']}v{$order['payment_ver']}",
            'Amount' => ($order['amount'] + $order['shipping']) * 100,
            'CurrencyID' => 'TWD',
            'PrdtName' => @$options['name'] ?: $qpay['PrdtName'],
            'ReturnURL' => get_url(APP_ROOT . 'payment/qpay-credit-card-return'),
            'BackendURL' => get_url(APP_ROOT . 'payment/qpay-credit-card-notify'),
            'PayType' => 'C', //信用卡
            'CardParam' => $param,
        ]);

        if ($response) {
            if ($response['Status'] === 'S') {
                $order['cashier'] = $response['CardParam']['CardPayURL'];
                $order['payment_request'] = json_encode($response, JSON_UNESCAPED_UNICODE);
                $order['payment_response'] = null;

                return $order;
            } else {
                return false;
            }
        }

        return null;
    }

    private static function decrypt($text, $key, $iv) {
        $data = hex2bin($text);

        $result = openssl_decrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);

        $padding = ord($result[strlen($result) - 1]);

        return json_decode(substr($result, 0, -$padding), true);
    }

    private static function encrypt($data, $key, $iv) {
        $text = json_encode($data);

        $padding = 16 - (strlen($text) % 16);

        $result = openssl_encrypt($text . str_repeat(chr($padding), $padding), 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);

        return strtoupper(bin2hex($result));
    }

    private static function getIV($nonce) {
        $data = hash('sha256', $nonce);

        return strtoupper(substr($data, strlen($data) - 16, 16));
    }

    private static function getSign($data, $nonce, $hashId) {
        $data = array_filter($data);

        ksort($data);

        $list = [];

        foreach ($data as $name => $value) {
            if (!is_array($value)) {
                $list[] = "{$name}={$value}";
            }
        }

        return strtoupper(hash('sha256', implode('&', $list) . $nonce . $hashId));
    }

    private static function request($url, $data) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => ['Content-type: application/json; charset=utf-8'],
            CURLOPT_NOBODY => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSLVERSION => 6, // TLS 1.2
        ]);

        $result = json_decode(curl_exec($ch), true);

        curl_close($ch);

        return $result;
    }

    private static function xor($data1, $data2) {
        $result = [];

        for ($i = 0; $i < strlen($data1); $i += 2) {
            $value1 = intval(base_convert(substr($data1, $i, 2), 16, 10));
            $value2 = intval(base_convert(substr($data2, $i, 2), 16, 10));

            $value = base_convert($value1 ^ $value2, 10, 16);

            $result[] = strlen($value) < 2 ? "0{$value}" : $value;
        }

        return strtoupper(implode($result));
    }

}
