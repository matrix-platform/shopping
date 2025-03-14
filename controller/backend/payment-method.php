<?php //>

return new class('PaymentMethod') extends matrix\web\backend\ListController {

    protected function init() {
        $this->columns(['title']);
    }

};
