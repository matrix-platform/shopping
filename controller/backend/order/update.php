<?php //>

return new class('Order') extends matrix\web\backend\UpdateController {

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

        if ($data) {
            switch ($data['status']) {
            case 1:
                if ($form['status'] === 2) {
                    $names = ['pay_time'];
                } else if ($form['status'] === 9) {
                    $names = ['cancel_time'];
                } else if ($form['status'] !== 1) {
                    return ['error' => 'error.invalid-order-status'];
                }
                break;
            case 2:
                if ($form['status'] === 2) {
                    $names = ['pay_time'];
                } else if ($form['status'] === 3) {
                    $names = ['drawback_time'];
                } else {
                    return ['error' => 'error.invalid-order-status'];
                }
                break;
            case 3:
                if (!in_array($form['status'], [2, 3])) {
                    return ['error' => 'error.invalid-order-status'];
                }
                $names = ['drawback_time'];
                break;
            case 9:
                if (!in_array($form['status'], [1, 9])) {
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

            $data = $model->update($data);
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

        return $this->subprocess($form, ['success' => true, 'data' => $data]);
    }

};
