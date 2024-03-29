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
            'ConsigneePost' => substr($order['post_code'], 0, 3),
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

            logging('kerry')->error('ShipmentDelivery', $response);

            return null;
        }

        return false;
    }

    public static function applyShipmentV2($order) {
        $cfg = load_cfg('kerry');

        $number = intval($cfg['BLN-prefix']) + db()->next($cfg['BLN-sequence']);

        $data = [
            'ShipDate' => date('Ymd'),
            'Shipper' => $cfg['Shipper'],
            'CustomerNo' => $cfg['CustomerNo'],
            'BLN' => "{$number}",
            'Consignee' => $order['name'],
            'ConsigneePost' => substr($order['post_code'], 0, 3),
            'ConsigneeAdd' => $order['address'],
            'ConsigneePhone' => $order['phone'],
            'Piece' => 1,
            'Temperature' => $cfg['Temperature'],
        ];

        $response = json_decode(self::request("{$cfg['url']}V2/Shipment", [$data], $cfg), true);

        if ($response) {
            if (@$response['Result'] === '01') {
                return $data['BLN'];
            }

            logging('kerry')->error('V2/Shipment', $response);

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
            'ConsigneePost' => substr($order['post_code'], 0, 3),
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

            logging('kerry')->error('PickupV2', $response);

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
                'ConsigneePost' => substr($item['order']['post_code'], 0, 3),
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
                'ConsigneePost' => substr($item['order']['post_code'], 0, 3),
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

    public static function listTracing($list) {
        $cfg = load_cfg('kerry');
        $data = [];

        foreach ($list as $item) {
            $data[] = [
                'BLN' => $item['shipping_no'],
            ];
        }

        $response = json_decode(self::request('https://KerryTracingEDI.kerrytj.com/api/Tracing/BLNListTracing', $data, $cfg), true);

        if ($response) {
            if (@$response['Status'] === '1' && is_array(@$response['Result'])) {
                $result = [];

                foreach ($response['Result'] as $item) {
                    $result[$item['BLN']] = $item['CargoTracing'];
                }

                return $result;
            }

            logging('kerry')->error('api/Tracing/BLNListTracing', $response);

            return null;
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
