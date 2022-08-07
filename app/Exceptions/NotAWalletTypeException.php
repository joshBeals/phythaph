<?php
namespace App\Exceptions;

class NotAWalletTypeException extends \Exception
{

    public $message = "The specified wallet type is not a valid wallet type";

}
