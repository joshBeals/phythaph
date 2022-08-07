<?php
namespace App\Exceptions;

class SavingTypeMismatchException extends \Exception
{

    private $mismatchType;
    public $message = "The savings is not a ";

    public function __construct($mismatchType = null)
    {
        if ($mismatchType) {
            $this->mismatchType = $mismatchType;
            $this->message .= $this->mismatchType;
        } else {
            $this->message .= "valid type";
        }

    }

}
