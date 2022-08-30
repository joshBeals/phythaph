<?php

namespace App\Models;

use App\Classes\Helper;
use App\Models\Base\Model;
use App\Traits\Disbursal;
use Illuminate\Support\Facades\Auth;

class UserPayoutRequest extends Model
{
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;
    use Disbursal;
    use \Illuminate\Database\Eloquent\SoftDeletes;

    const STATUSES = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'authorized' => 'Authorized',
        'disbursed' => 'Disbursed',
        'processing' => 'Processing',
        'processed' => 'Processed',
    ];

    const SOURCES = [
        'id' => 'ID', // For backward comp only
        'savings' => 'Saving',
        'wallet' => 'Wallet',
    ];

    protected $fillable = [
        'user_id',
        'entity_id',
        'amount',
        'penalty',
        'disburse_amount',
        'note',
        'source',
        'bank_id',
    ];

    protected $dates = ['deleted_at', 'disbursed_at', 'processed_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function bank()
    {
        return $this->belongsTo(UserBank::class);
    }

    public static function boot()
    {
        Parent::boot();
        static::created(function ($pr) {

            if (config('app.env') === 'local') {
                return;
            }

            $user = $pr->user;

            $data = [
                'Customer' => $user->name,
                'Source' => $pr->source,
                'Amount' => Helper::formatNumber($pr->amount),
                'Penalty (if any)' => Helper::formatNumber($pr->penalty),
                'Amount to disburse' => Helper::formatNumber($pr->disburse_amount),
                'Note' => $pr->note ?? 'N/A',
            ];

            if ($pr->wallet) {
                $data['Wallet'] = $pr->getWalletName();
            }

            // \App\Classes\SlackSiteNotification::send("New Payout Request", $data);

        });

    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function process_admin()
    {
        return $this->belongsTo(Admin::class, 'processed_by');
    }

    public function getCustomerNameAttribute()
    {
        return $this->user()->first()->name;
    }

    public function getDateAttribute()
    {
        return $this->created_at;
    }

    private function addProcessMeta()
    {
        $this->dbUpdate([
            'processed_at' => $this->processed_at ?? now(),
            'disbursed_at' => $this->disbursed_at ?? now(),
            'processed_by' => $this->processed_by ?? Auth::guard('admin')->user()->id ?? null,
        ]);
    }

    public function markAsProcessed()
    {

        $this->dbUpdate([
            'status' => 'processed',
            'pending_disbursal' => false,
        ]);

        $this->addProcessMeta();

        return true;
    }

    public function processTransfer()
    {

        $this->dbUpdate([
            'status' => 'processing',
            'disbursed_at' => now(),
            'processed_by' => Auth::guard('admin')->user()->id ?? null,
            'pending_disbursal' => false,
        ]);

        $this->attemptDisburse();

        return true;
    }

    /**
     * {@inheritdoc}
     * Check if this entity can disburse
     *
     * @return bool
     */
    protected function canDisburse(): bool
    {
        return $this->status === 'processing' && !$this->pending_disbursal;

    }

    /**
     * Set the transfer parameters
     *
     * @return void
     */
    protected function setTransferParameters(): void
    {
        $user = $this->user()->first();
        $this->processorSecretKey = config('paystack.secretKey', null);
        $this->disburseReason = 'Customer';
        if ($user) {
            $this->disburseReason .= " #" . $user->id;
        }
        $this->disburseAmount = $this->disburse_amount ?? $this->amount;

        $this->disburseReason .= ' Payout For Disbursal #' . $this->id;
        $this->transactionType = 'customer_payout';
        $this->disbursalType = 'customer_payout';
    }

    /**
     * {@inheritdoc}
     * Called when there is a disburse success
     * Useful for setting value
     *
     * @return void
     */
    protected function disburseSuccess(): void
    {
        $this->markAsProcessed();
    }

    /**
     * {@inheritdoc}
     * Called when there is a disburse success
     * Useful for setting value
     *
     * @return void
     */
    protected function onPendingDisbursal(): void
    {
        $this->dbUpdate([
            'status' => 'disbursed',
            'pending_disbursal' => true,
        ]);
    }

    /**
     * Called when transaction is generted before main disbursal attempt
     * Useful for setting value
     *
     * @return void
     */
    protected function onTransactionGeneration(Transaction $txn): void
    {
        $this->dbUpdate([
            'transaction_id' => $txn->id,
        ]);
        $user = $this->user()->first();
        if ($user) {
            $txn->user_id = $user->id;
            $txn->save();
        }
    }

    /**
     * Get a decorated wallet name
     */
    public function getWalletName(): ?string
    {
        if (!$this->wallet) {
            return null;
        }

        return ucwords(mb_ereg_replace("_", " ", $this->wallet));
    }

}
