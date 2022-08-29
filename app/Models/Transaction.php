<?php

namespace App\Models;

use App\Classes\Helper;
use App\Models\Base\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 *
 * Transaction amount has to be registered in Kobo
 */

class Transaction extends Model
{
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;
    use SoftDeletes;

    const TYPES = [
        'add_card', 'customer_charge', 'third_party_charges', 'expense_retirement', 'membership_subscription', 'wallet_topup'
    ];

    const TRANSFER_CLASSES = [
        'loan_disbursal' => Loan::class,
        'customer_payout' => UserPayoutRequest::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Use to modify the transaction types when building migration schemas
     */
    public static function modifyTransactionTypes()
    {
        \DB::statement("ALTER TABLE `transactions` MODIFY COLUMN type ENUM(" .
            implode(", ", array_map(
                function ($item) {
                    return "'" . $item . "'";
                },
                static::TYPES
            ))
            . ") NOT NULL DEFAULT 'customer_charge';");
    }

    /**
     * Initialize transaction before hitting payment processor
     *
     * @param float $amount     The amount in naira to initialize
     * @param Object $payload   The payload data to store against the transaction
     *
     * @return Transaction|null
     */
    public static function initialize(float $amount, Object $payload = null)
    {
        $reference = Helper::makeTxnRef();

        $t = new Self;

        $t->reference = $reference;
        $t->status = 'pending';

        $t->amount = doubleval($amount) * 100;
        if ($payload) {
            $t->payload = json_encode($payload);
        }

        if (isset($payload->user_id)) {
            $t->user_id = $payload->user_id;
        }

        if (isset($payload->description)) {
            $t->description = $payload->description;
        }

        if (isset($payload->type)) {
            $t->type = $payload->type;
        }

        $t->save();

        return $t;
    }

    /**
     * Process transfers
     *
     * @param Object $result    Transafer response payload
     */
    public function processtransfer(Object $result)
    {
        // Its not your thing, don't do it
        if ($this->status = 'success' || !$this->disbursal_type || !$this->entity_id) {
            return;
        }

        $klass = Self::TRANSFER_CLASSES[$this->disbursal_type] ?? null;

        // I must be a bad programmer
        if (!$klass) {
            return;
        }

        $model = $klass::find($this->entity_id);

        // Even wierder
        if (!$model) {
            return;
        }

        return $model->processDisburseResponse($result);

    }

    public function getCustomerNameAttribute()
    {
        return $this->user()->first()->name;
    }

}
