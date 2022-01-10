<?php //>

namespace matrix\shopping\payment;

class Newebpay {

    public static function applyAtm($order, $member) {
        $args = load_cfg('newebpay');
        $args['TimeStamp'] = time();
        $args['MerchantOrderNo'] = "{$order['order_no']}v{$order['payment_ver']}";
        $args['Amt'] = $order['amount'] + $order['shipping'];
        $args['CustomerURL'] = get_url(APP_ROOT . 'payment/newebpay-atm-return');
        $args['NotifyURL'] = get_url(APP_ROOT . 'payment/newebpay-atm-notify');
        $args['LangType'] = (LANGUAGE === 'tw') ? 'zh-tw' : 'en';
        $args['Email'] = $member['mail'];
        $args['VACC'] = 1;

        $response = self::apply($args);

        if ($response) {
            $order['cashier'] = $args['url'];
            $order['payment_request'] = json_encode($response, JSON_UNESCAPED_UNICODE);
            $order['payment_response'] = null;

            return $order;
        }

        return $response;
    }

    public static function applyCreditCard($order, $member) {
        $args = load_cfg('newebpay');
        $args['TimeStamp'] = time();
        $args['MerchantOrderNo'] = "{$order['order_no']}v{$order['payment_ver']}";
        $args['Amt'] = $order['amount'] + $order['shipping'];
        $args['ReturnURL'] = get_url(APP_ROOT . 'payment/newebpay-credit-card-return');
        $args['NotifyURL'] = get_url(APP_ROOT . 'payment/newebpay-credit-card-notify');
        $args['LangType'] = (LANGUAGE === 'tw') ? 'zh-tw' : 'en';
        $args['Email'] = $member['mail'];
        $args['CREDIT'] = 1;

        $response = self::apply($args);

        if ($response) {
            $order['cashier'] = $args['url'];
            $order['payment_request'] = json_encode($response, JSON_UNESCAPED_UNICODE);
            $order['payment_response'] = null;

            return $order;
        }

        return $response;
    }

    public static function applyCvs($order, $member) {
        $args = load_cfg('newebpay');
        $args['TimeStamp'] = time();
        $args['MerchantOrderNo'] = "{$order['order_no']}v{$order['payment_ver']}";
        $args['Amt'] = $order['amount'] + $order['shipping'];
        $args['CustomerURL'] = get_url(APP_ROOT . 'payment/newebpay-cvs-return');
        $args['NotifyURL'] = get_url(APP_ROOT . 'payment/newebpay-cvs-notify');
        $args['LangType'] = (LANGUAGE === 'tw') ? 'zh-tw' : 'en';
        $args['Email'] = $member['mail'];
        $args['CVS'] = 1;

        $response = self::apply($args);

        if ($response) {
            $order['cashier'] = $args['url'];
            $order['payment_request'] = json_encode($response, JSON_UNESCAPED_UNICODE);
            $order['payment_response'] = null;

            return $order;
        }

        return $response;
    }

    public function decrypt($info, $key, $iv) {
        $data = openssl_decrypt(hex2bin($info), 'aes-256-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);

        $slast = ord(substr($data, -1));
        $slastc = chr($slast);
        $pcheck = substr($data, -$slast);

        if (preg_match("/$slastc{" . $slast . "}/", $data)) {
            return substr($data, 0, strlen($data) - $slast);
        } else {
            return false;
        }
    }

    private static function apply($args) {
        $names = [
            'MerchantID',
            'RespondType',
            'TimeStamp',
            'Version',
            'LangType',
            'MerchantOrderNo',
            'Amt',
            'ItemDesc',
            'TradeLimit',
            'CREDIT',
            'VACC',
            'CVS',
            'CustomerURL',
            'ReturnURL',
            'NotifyURL',
            'Email',
        ];

        $data = array_intersect_key($args, array_flip($names));
        $info = self::encrypt($data, $args['HashKey'], $args['HashIV']);
        $hash = strtoupper(hash('sha256', "HashKey={$args['HashKey']}&{$info}&HashIV={$args['HashIV']}"));

        return [
            'MerchantID' => $args['MerchantID'],
            'TradeInfo' => $info,
            'TradeSha' => $hash,
            'Version' => $args['Version'],
            'url' => $args['url'],
        ];
    }

    private function encrypt($parameter, $key, $iv) {
        $data = http_build_query($parameter);
        $length = strlen($data);
        $pad = 32 - ($length % 32);

        $data = $data . str_repeat(chr($pad), $pad);

        return trim(bin2hex(openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv)));
    }

}
