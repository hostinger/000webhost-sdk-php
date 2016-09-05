<?php

class WebhostApiException extends \Exception {

    private $data;

    /**
     * WebhostApiException constructor.
     * @param string $message
     * @param int $data
     */
    public function __construct($message, $data = null){
        $this->data = $data;
        parent::__construct($message);
    }

    /**
     * Returns data that was returned by the server
     * @return array|string
     */
    public function getResponseData(){
        $decoded = json_decode($this->data, 1);

        if(is_null($decoded)){
            return $this->data;
        }else{
            return $decoded;
        }
    }
}