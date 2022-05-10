<?php //>

namespace matrix\shopping\payment;

class Sunpay {

    public static function applyAtm($order, $member) {
        $args = load_cfg('sunpay');
        $args['web'] = $args['atm'];
        $args['MN'] = $order['amount'] + $order['shipping'];
        $args['Td'] = "{$order['order_no']}v{$order['payment_ver']}";
        $args['sna'] = $order['name'];
        $args['sdt'] = $order['phone'];
        $args['email'] = $member['mail'] ?: '';
        $args['DueDate'] = date('Ymd', time() + 86400 * $args['payment-days']);
        $args['UserNo'] = $member['id'];
        $args['OrderInfo'] = @$order['info'];
        $args['ProductPrice1'] = $args['MN'];
        $args['ProductQuantity1'] = 1;
        $args['AgencyType'] = '2';

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
        $args = load_cfg('sunpay');
        $args['web'] = $args['credit-card'];
        $args['MN'] = $order['amount'] + $order['shipping'];
        $args['Td'] = "{$order['order_no']}v{$order['payment_ver']}";
        $args['sna'] = $order['name'];
        $args['sdt'] = $order['phone'];
        $args['email'] = $member['mail'] ?: '';
        $args['Card_Type'] = '0';
        $args['UserNo'] = $member['id'];
        $args['OrderInfo'] = @$order['info'];
        $args['ProductPrice1'] = $args['MN'];
        $args['ProductQuantity1'] = 1;

        $response = self::apply($args);

        if ($response) {
            $order['cashier'] = $args['url'];
            $order['payment_request'] = json_encode($response, JSON_UNESCAPED_UNICODE);
            $order['payment_response'] = null;

            return $order;
        }

        return $response;
    }

    public static function applyPayCode($order, $member) {
        $args = load_cfg('sunpay');
        $args['web'] = $args['pay-code'];
        $args['MN'] = $order['amount'] + $order['shipping'];
        $args['Td'] = "{$order['order_no']}v{$order['payment_ver']}";
        $args['sna'] = $order['name'];
        $args['sdt'] = $order['phone'];
        $args['email'] = $member['mail'] ?: '';
        $args['DueDate'] = date('Ymd', time() + 86400 * $args['payment-days']);
        $args['UserNo'] = $member['id'];
        $args['OrderInfo'] = @$order['info'];
        $args['ProductPrice1'] = $args['MN'];
        $args['ProductQuantity1'] = 1;

        $response = self::apply($args);

        if ($response) {
            $order['cashier'] = $args['url'];
            $order['payment_request'] = json_encode($response, JSON_UNESCAPED_UNICODE);
            $order['payment_response'] = null;

            return $order;
        }

        return $response;
    }

    private static function apply($args) {
        $defaults = [
            'web' => '',
            'MN' => '',
            'OrderInfo' => '',
            'Td' => '',
            'sna' => '',
            'sdt' => '',
            'email' => '',
            'note1' => '',
            'note2' => '',
            'Card_Type' => '',
            'Country_Type' => '',
            'Term' => '',
            'DueDate' => '',
            'UserNo' => '',
            'BillDate' => '',
            'ProductName1' => '',
            'ProductPrice1' => '',
            'ProductQuantity1' => '',
            'AgencyType' => '',
            'AgencyBank' => '',
            'CargoFlag' => '',
            'StoreID' => '',
            'StoreName' => '',
            'BuyerCid' => '',
            'DonationCode' => '',
            'Carrier_ID' => '',
            'EDI' => '',
            'ChkValue' => '',
        ];

        $data = array_intersect_key(array_merge($defaults, $args), $defaults);
        $data['ChkValue'] = strtoupper(sha1("{$data['web']}{$args['password']}{$data['MN']}{$data['Term']}"));

        return $data;
    }

}
