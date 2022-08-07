<?php

namespace App\Exceptions;

class GreaterThanTenorException extends \Exception
{

    private $timeString;

    public $message;

    public function __construct(string $timeString = null)
    {
        $this->message = "The term ";

        if ($timeString) {
            $this->message .= "(" . $timeString . ") ";
        }

        $this->message .= "is greater than the tenor of this entity";
    }
}
