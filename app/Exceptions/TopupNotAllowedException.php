<?php
namespace App\Exceptions;

class TopupNotAllowedException extends \Exception
{

    private $accountType;
    public $message = "Topup not allowed";

    public function __construct($accountType = null)
    {
        if ($accountType) {
            $this->accountType = $accountType;
            $this->message .= " on " . $this->accountType;
        }

    }

}
