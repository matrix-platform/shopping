<?php //>

namespace matrix\shopping\shipping;

use matrix\web\UserController;

class EcpayOrder extends UserController {

    public function __construct($shipment) {
        $this->values = ['shipment' => $shipment];
    }

    public function available() {
        if ($this->method() === 'GET') {
            $pattern = preg_quote($this->name(), '/');

            return preg_match("/^{$pattern}\/[\d,]+?$/", $this->path());
        }

        return false;
    }

    protected function process($form) {
        $list = $this->queryOrders();

        if (!$list) {
            return ['view' => '404.php'];
        }

        $cfg = load_cfg('ecpay-shipping');

        $data = [
            'MerchantID' => $cfg['MerchantID'],
            'AllPayLogisticsID' => implode(',', array_column($list, 'AllPayLogisticsID')),
            'CVSPaymentNo' => implode(',', array_column($list, 'CVSPaymentNo')),
        ];

        if ($this->shipment() === 'UNIMARTC2C') {
            $data['CVSValidationNo'] = implode(',', array_column($list, 'CVSValidationNo'));
        }

        $data['CheckMacValue'] = Ecpay::checksum($data, $cfg['HashKey'], $cfg['HashIV']);

        return [
            'success' => true,
            'view' => 'ecpay/shipping/cvs-order.twig',
            'path' => $cfg['url'] . $this->getPath(),
            'data' => $data,
        ];
    }

    protected function queryOrders() {
        return [];
    }

    private function getPath() {
        switch ($this->shipment()) {
        case 'UNIMARTC2C':
            return 'PrintUniMartC2COrderInfo';
        case 'FAMIC2C':
            return 'PrintFAMIC2COrderInfo';
        case 'HILIFEC2C':
            return 'PrintHILIFEC2COrderInfo';
        case 'OKMARTC2C':
            return 'PrintOKMARTC2COrderInfo';
        default:
            return null;
        }
    }

}
