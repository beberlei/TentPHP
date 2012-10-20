<?php

namespace TentPHP;

class UserAuthorization
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getMacKey()
    {
        return $this->data['mac_key'];
    }

    public function getMacAlgorithm()
    {
        return $this->data['mac_algorithm'];
    }

    public function getAccessToken()
    {
        return $this->data['access_token'];
    }

    public function getTokenType()
    {
        return $this->data['token_type'];
    }
}

