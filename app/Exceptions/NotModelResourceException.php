<?php
namespace App\Exceptions;

class NotModelResourceException extends \Exception
{

    public $message = "The resource does not belong to the model";

}
