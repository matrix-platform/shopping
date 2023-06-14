<?php //>

return new class() extends matrix\web\Controller {

    protected function init() {
        $this->view('ecpay/shipping/cvs-map.twig');
    }

};
