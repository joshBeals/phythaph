<?php

namespace App\Exceptions;

class CannotUpdateBonusesException extends \Exception
{
    public $message = "Bonuses on this entity can no longer be updated, either a bonus has already been remitted or the first bonus has passed maturity";
}
