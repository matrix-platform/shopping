<?php //>

return new class() extends matrix\web\Controller {

    public function available() {
        return ($this->method() === 'POST' && $this->name() === $this->path());
    }

    public function verify() {
        return true;
    }

    protected function init() {
        $this->view('ecpay/shipping/cvs-map-return.twig');
    }

};
