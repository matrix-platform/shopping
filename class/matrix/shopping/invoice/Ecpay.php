<?php //>

namespace matrix\shopping\invoice;

class Ecpay {

    public static function applyInvoice($data) {
        $cfg = load_cfg('ecpay-invoice');

        $data['MerchantID'] = $cfg['MerchantID'];
        $data['Print'] = '0';
        $data['Donation'] = '0';
        $data['InvType'] = '07';

        if (key_exists('CustomerIdentifier', $data)) {
            $data['CarrierType'] = '';
            $data['CarrierNum'] = '';
        } else if (!key_exists('CarrierType', $data)) {
            $data['CarrierType'] = '1';
            $data['CarrierNum'] = '';
        }

        $param = [
            'MerchantID' => $cfg['MerchantID'],
            'RqHeader' => ['Timestamp' => time()],
            'Data' => $data,
        ];

        $result = ['request' => $param];

        $param['Data'] = self::encrypt($param['Data'], $cfg['HashKey'], $cfg['HashIV']);

        $request = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($param),
            ],
        ];

        $response = @file_get_contents("{$cfg['url']}Issue", false, stream_context_create($request));

        if ($response) {
            $response = json_decode($response, true);

            if ($response) {
                $response['Data'] = self::decrypt($response['Data'], $cfg['HashKey'], $cfg['HashIV']);
            }
        }

        $result['response'] = $response;

        return $result;
    }

    public static function verifyCarrierNum($carrier) {
        $cfg = load_cfg('ecpay-invoice');

        $data = [
            'MerchantID' => $cfg['MerchantID'],
            'BarCode' => $carrier,
        ];

        $param = [
            'MerchantID' => $cfg['MerchantID'],
            'RqHeader' => ['Timestamp' => time()],
            'Data' => self::encrypt($data, $cfg['HashKey'], $cfg['HashIV']),
        ];

        $request = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($param),
            ],
        ];

        $response = @file_get_contents("{$cfg['url']}CheckBarcode", false, stream_context_create($request));

        if ($response) {
            $response = json_decode($response, true);

            if ($response) {
                $result = self::decrypt($response['Data'], $cfg['HashKey'], $cfg['HashIV']);

                if ($result['RtnCode'] === 1 && $result['IsExist'] === 'Y') {
                    return true;
                }
            }
        }

        return false;
    }

    private static function decrypt($text, $key, $iv) {
        return json_decode(urldecode(openssl_decrypt($text, 'aes-128-cbc', $key, 0, $iv)), true);
    }

    private static function encrypt($data, $key, $iv) {
        $text = urlencode(json_encode($data));

        $text = str_replace('%20', '+', $text);
        $text = str_replace('%21', '!', $text);
        $text = str_replace('%28', '(', $text);
        $text = str_replace('%29', ')', $text);
        $text = str_replace('%2a', '*', $text);
        $text = str_replace('%2d', '-', $text);
        $text = str_replace('%2e', '.', $text);
        $text = str_replace('%5f', '_', $text);
        $text = str_replace('%2A', '*', $text);
        $text = str_replace('%2D', '-', $text);
        $text = str_replace('%2E', '.', $text);
        $text = str_replace('%5F', '_', $text);

        return openssl_encrypt($text, 'aes-128-cbc', $key, 0, $iv);
    }

}
