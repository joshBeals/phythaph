<?php
namespace App\Exceptions;

class NotAModelException extends \Exception
{

    public $message = "Not a Model or Model not found";

}
