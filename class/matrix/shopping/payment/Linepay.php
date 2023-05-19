<?php //>

namespace matrix\shopping\payment;

class Linepay {

    public static function applyPayment($order, $member) {
        $linepay = load_cfg('linepay');

        $amount = $order['amount'] + $order['shipping'];

        $param = [
            'amount' => $amount,
            'currency' => $linepay['currency'],
            'orderId' => "{$order['order_no']}v{$order['payment_ver']}",
            'packages' => [[
                'id' => "{$order['id']}",
                'amount' => $amount,
                'products' => [[
                    'name' => $linepay['product-name'],
                    'quantity' => 1,
                    'price' => $amount,
                ]],
            ]],
            'redirectUrls' => [
                'confirmUrl' => get_url(APP_ROOT . 'payment/linepay-return'),
                'cancelUrl' => get_url(APP_ROOT . 'payment/linepay-cancel'),
            ],
        ];

        $response = self::request('/v3/payments/request', $linepay, $param);

        if ($response) {
            if ($response['returnCode'] === '0000') {
                $order['cashier'] = $response['info']['paymentUrl']['web'];
                $order['cashier_type'] = 'redirect';
                $order['payment_request'] = json_encode($param, JSON_UNESCAPED_UNICODE);
                $order['payment_response'] = json_encode($response, JSON_UNESCAPED_UNICODE);

                return $order;
            } else {
                return false;
            }
        }

        return null;
    }

    public static function request($api, $linepay, $param) {
        $nonce = round(microtime(true) * 1000);
        $data = json_encode($param, JSON_UNESCAPED_UNICODE);
        $authorization = base64_encode(hash_hmac('sha256', "{$linepay['channel-secret']}{$api}{$data}{$nonce}", $linepay['channel-secret'], true));

        $headers = [
            'Content-Type: application/json',
            "X-LINE-ChannelId: {$linepay['channel-id']}",
            "X-LINE-Authorization-Nonce: {$nonce}",
            "X-LINE-Authorization: {$authorization}",
        ];

        $request = [
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $data,
            ],
        ];

        logging('linepay')->info($api, $request);

        $response = @file_get_contents("{$linepay['url']}{$api}", false, stream_context_create($request));

        logging('linepay')->info($response);

        return json_decode($response, true);
    }

}
