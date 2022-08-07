<?php
namespace App\Exceptions;

class MinimumWithdrawalException extends \Exception
{

    private $minBalance;
    public $message = "Amount below minimum withdrawal";

    public function __construct($minBalance = null)
    {
        if ($minBalance) {
            $this->minBalance = $minBalance;
            $this->message .= ": a minimum of " . $minBalance . " Naira can be withdrawn";
        }

    }

}
