<?php
namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Attach logging functionality to object
 */
trait Logger
{
    /**
     * Log the object to default logger
     * @param mixed $data       The data to log     default the object
     * @param string $method    The log method to call  default debug
     *
     * @return void
     */
    public function debug($data = null, $method = 'debug'): void
    {
        Log::{$method}($data || $this);

        if (config('app.debug', false)) {
            throw $data;
        }

    }

    public function log($data): void
    {
        $this->debug($data);
    }
}
