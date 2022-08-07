<?php
namespace App\Traits;

use App\Classes\Helper;
use App\Classes\PaystackApi;
use App\PendingDisbursal;
use App\TransactionHistory;
use Illuminate\Support\Facades\Log;

trait GenericDisbursal
{
    protected $dates = ['deleted_at', 'disbursed_at', 'processed_at'];

    public function transaction()
    {
        return $this->belongsTo(\App\TransactionHistory::class);
    }

    public function process_admin()
    {
        return $this->belongsTo(\App\Admin::class, 'processed_by');
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
}
