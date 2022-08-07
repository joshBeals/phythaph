<?php

namespace App\Exceptions;

class NotAllowedBonusIntervalException extends \Exception
{

    private $timeString;

    public $message;

    public function __construct(string $timeString = null)
    {
        $this->message = "The term ";

        if ($timeString) {
            $this->message .= "(" . $timeString . ") ";
        }

        $this->message .= "cannot be computed as a bonus interval on a per month basis";
    }
}
