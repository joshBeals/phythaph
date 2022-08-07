<?php

namespace App\Interfaces;

interface Apiable
{

    public function send($endpoint, $data = null);

}
