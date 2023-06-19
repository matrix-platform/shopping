<?php //>

namespace matrix\shopping;

use matrix\web\backend\UpdateController;

class UpdateOrderController extends UpdateController {

    public function __construct() {
        parent::__construct('Order');
    }

    protected function modify($data, $form) {
        return $data;
    }

    protected function preprocess($form) {
        if (@$form['drawback_time']) {
            $form['status'] = 3;
        } else if (@$form['cancel_time']) {
            $form['status'] = 9;
        } else if (@$form['pay_time']) {
            $form['status'] = 2;
        } else {
            $form['status'] = 1;
        }

        return $form;
    }

    protected function process($form) {
        $model = $this->table()->model();
        $data = $model->get($this->formId());
        $status = null;

        if ($data) {
            switch ($data['status']) {
            case 1:
                if ($form['status'] === 1) {
                    $names = [];
                } else if ($form['status'] === 2) {
                    $status = 2;
                    $names = ['pay_time'];
                } else if ($form['status'] === 9) {
                    $status = 9;
                    $names = ['cancel_time'];
                } else {
                    return ['error' => 'error.invalid-order-status'];
                }
                break;
            case 2:
                if ($form['status'] === 2) {
                    $names = ['pay_time'];
                } else if ($form['status'] === 3) {
                    $status = 3;
                    $names = ['drawback_time'];
                } else {
                    return ['error' => 'error.invalid-order-status'];
                }
                break;
            case 3:
                if ($form['status'] !== 3) {
                    return ['error' => 'error.invalid-order-status'];
                }
                $names = ['drawback_time'];
                break;
            case 9:
                if ($form['status'] !== 9) {
                    return ['error' => 'error.invalid-order-status'];
                }
                $names = ['cancel_time'];
                break;
            }

            foreach ($names as $name) {
                $data[$name] = $form[$name];
            }

            $data['remark'] = $form['remark'];
            $data['status'] = $form['status'];

            $data = $model->update($this->modify($data, $form));
        }

        if ($data === null) {
            return ['error' => 'error.data-not-found'];
        }

        if ($data === false) {
            if ($this->table()->versionable()) {
                return ['error' => 'error.data-outdated'];
            }

            return ['error' => 'error.update-failed'];
        }

        return $this->subprocess($form, ['success' => true, 'data' => $data, 'status' => $status]);
    }

}
