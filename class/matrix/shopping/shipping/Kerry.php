<?php //>

namespace matrix\shopping\shipping;

class Kerry {

    public static function applyShipment($order) {
        $cfg = load_cfg('kerry');

        $number = intval($cfg['BLN-prefix']) + db()->next($cfg['BLN-sequence']);

        $data = [
            'ShipDate' => date('Ymd'),
            'CustomerNo' => $cfg['CustomerNo'],
            'Consignee' => $order['name'],
            'ConsigneePost' => $order['post_code'],
            'ConsigneeAdd' => $order['address'],
            'ConsigneePhone' => $order['phone'],
            'BLN' => "{$number}",
            'Shipper' => $cfg['Shipper'],
            'ShipperPost' => $cfg['ShipperPost'],
            'ShipperAdd' => $cfg['ShipperAdd'],
            'ShipperTel' => $cfg['ShipperTel'],
            'Temperature' => $cfg['Temperature'],
        ];

        $response = json_decode(self::request("{$cfg['url']}ShipmentDelivery", [$data], $cfg), true);

        if ($response) {
            if (@$response['Result'] === '01') {
                return $data['BLN'];
            }

            return null;
        }

        return false;
    }

    public static function applyReturn($order, $number, $piece = 1) {
        $cfg = load_cfg('kerry');

        $data = [
            'BLN' => $number,
            'CustomerNo' => $cfg['CustomerNo'],
            'Consignee' => $order['name'],
            'ConsigneeTel1' => $order['phone'],
            'ConsigneePost' => $order['post_code'],
            'ConsigneeAdd' => $order['address'],
            'Shipper' => $cfg['Shipper'],
            'ShipperTel1' => $cfg['ShipperTel'],
            'ShipperPost' => $cfg['ShipperPost'],
            'ShipperAdd' => $cfg['ShipperAdd'],
            'Piece' => $piece,
        ];

        $response = json_decode(self::request("{$cfg['url']}PickupV2", [$data], $cfg), true);

        if ($response) {
            if (@$response['Result'] === '01') {
                return true;
            }

            return null;
        }

        return false;
    }

    public static function downloadAddrLabel($list) {
        $cfg = load_cfg('kerry');
        $data = [];
        $names = [];

        foreach ($list as $item) {
            $data[] = [
                'ShipDate' => $item['date'],
                'CustomerNo' => $cfg['CustomerNo'],
                'Consignee' => $item['order']['name'],
                'ConsigneePost' => $item['order']['post_code'],
                'ConsigneeAdd' => $item['order']['address'],
                'ConsigneePhone' => $item['order']['phone'],
                'BLN' => $item['number'],
                'Shipper' => $cfg['Shipper'],
                'ShipperPost' => $cfg['ShipperPost'],
                'ShipperAdd' => $cfg['ShipperAdd'],
                'ShipperTel' => $cfg['ShipperTel'],
                'Piece' => $item['piece'],
                'Temperature' => $cfg['Temperature'],
            ];

            $names[] = $item['order']['order_no'];
        }

        $response = self::request("{$cfg['url']}Report/GetLabelPdfA4", $data, $cfg);

        if ($response) {
            if (json_decode($response, true)) {
                return null;
            }

            $folder = create_folder(APP_DATA . 'kerry');
            $file = $folder . '/' . implode('-', $names) . '.pdf';

            file_put_contents($file, $response);

            return $file;
        }

        return false;
    }

    public static function downloadShipmentDetails($list) {
        $cfg = load_cfg('kerry');
        $data = [];

        foreach ($list as $item) {
            $data[] = [
                'ShipDate' => $item['date'],
                'CustomerNo' => $cfg['CustomerNo'],
                'Consignee' => $item['order']['name'],
                'ConsigneePost' => $item['order']['post_code'],
                'ConsigneeAdd' => $item['order']['address'],
                'ConsigneePhone' => $item['order']['phone'],
                'BLN' => $item['number'],
                'Shipper' => $cfg['Shipper'],
                'ShipperPost' => $cfg['ShipperPost'],
                'ShipperAdd' => $cfg['ShipperAdd'],
                'ShipperTel' => $cfg['ShipperTel'],
                'Piece' => $item['piece'],
                'Temperature' => $cfg['Temperature'],
            ];
        }

        $response = self::request("{$cfg['url']}Report/GetDetailPdf", $data, $cfg);

        if ($response) {
            if (json_decode($response, true)) {
                return null;
            }

            $folder = create_folder(APP_DATA . 'kerry');
            $file = $folder . '/' . date('YmdHis') . '.pdf';

            file_put_contents($file, $response);

            return $file;
        }

        return false;
    }

    private static function request($url, $data, $cfg) {
        $token = base64_encode("{$cfg['username']}:{$cfg['password']}");

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', "Authorization: Basic {$token}"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

}
