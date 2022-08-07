<?php
namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Add manual Database update a the model
 */
trait DbUpdate
{
    /**
     * Update the model to avoid decoration issues
     *
     * @param array $data       The data to update
     * @param bool $refresh     Refresh the object when update is done
     * @param Closure $callback Callback after the update is done
     *
     * @return Self
     */
    public function dbUpdate(array $data, $refresh = false, \Closure $callback = null)
    {
        DB::table($this->getTable())->where('id', $this->id)
            ->update($data);

        if ($refresh) {
            $this->refresh();
        } else {
            // Manually overload the new data to the model
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
        }

        if ($callback) {
            $callback();
        }

        return $this;

    }
}
