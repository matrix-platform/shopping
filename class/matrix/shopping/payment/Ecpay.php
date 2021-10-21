<?php //>

namespace matrix\shopping\payment;

class Ecpay {

    public static function applyAtm($order, $member) {
        $args = load_cfg('ecpay');
        $args['MerchantTradeNo'] = "{$order['order_no']}v{$order['payment_ver']}";
        $args['MerchantTradeDate'] = date('Y/m/d H:i:s');
        $args['TotalAmount'] = $order['amount'] + $order['shipping'];
        $args['ReturnURL'] = url(APP_ROOT . 'payment/ecpay-atm-notify');
        $args['ChoosePayment'] = 'ATM';
        $args['ClientRedirectURL'] = url(APP_ROOT . 'payment/ecpay-atm-return');

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
        $args = load_cfg('ecpay');
        $args['MerchantTradeNo'] = "{$order['order_no']}v{$order['payment_ver']}";
        $args['MerchantTradeDate'] = date('Y/m/d H:i:s');
        $args['TotalAmount'] = $order['amount'] + $order['shipping'];
        $args['ReturnURL'] = url(APP_ROOT . 'payment/ecpay-credit-card-notify');
        $args['ChoosePayment'] = 'Credit';
        $args['OrderResultURL'] = url(APP_ROOT . 'payment/ecpay-credit-card-return');

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
        $args = load_cfg('ecpay');
        $args['MerchantTradeNo'] = "{$order['order_no']}v{$order['payment_ver']}";
        $args['MerchantTradeDate'] = date('Y/m/d H:i:s');
        $args['TotalAmount'] = $order['amount'] + $order['shipping'];
        $args['ReturnURL'] = url(APP_ROOT . 'payment/ecpay-cvs-notify');
        $args['ChoosePayment'] = 'CVS';
        $args['ClientRedirectURL'] = url(APP_ROOT . 'payment/ecpay-cvs-return');

        $response = self::apply($args);

        if ($response) {
            $order['cashier'] = $args['url'];
            $order['payment_request'] = json_encode($response, JSON_UNESCAPED_UNICODE);
            $order['payment_response'] = null;

            return $order;
        }

        return $response;
    }

    public static function checksum($data, $key, $iv) {
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

        return strtoupper(hash('sha256', $sign));
    }

    private static function apply($args) {
        $names = [
            'ChoosePayment',
            'EncryptType',
            'ItemName',
            'Language',
            'MerchantID',
            'MerchantTradeDate',
            'MerchantTradeNo',
            'PaymentType',
            'ReturnURL',
            'TotalAmount',
            'TradeDesc',
        ];

        switch (@$args['ChoosePayment']) {
        case 'ATM':
            $names[] = 'ClientRedirectURL';
            $names[] = 'ExpireDate';
            break;
        case 'CVS':
            $names[] = 'ClientRedirectURL';
            $names[] = 'StoreExpireDate';
            break;
        case 'Credit':
            $names[] = 'OrderResultURL';
            $names[] = 'UnionPay';
            break;
        default:
            return false;
        }

        $data = array_intersect_key($args, array_flip($names));
        $data['CheckMacValue'] = self::checksum($data, @$args['HashKey'], @$args['HashIV']);

        return $data;
    }

}
