<?php

namespace App\Exceptions;

class InvalidOrUnsetToken extends \Exception
{

    public $message = "Invalid or unset token or API key ";

}
