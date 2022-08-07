<?php
namespace App\Traits;

use App\Classes\Helper;
use App\Classes\PaystackApi;
use App\Models\Transaction;
use App\PendingDisbursal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait Disbursal
{
    /**
     * Secret Key used to perform the transactions
     *
     * @var string
     */
    protected $processorSecretKey;

    /**
     * The reciepient code to send the transfer to
     *
     * @var string
     */
    protected $recipientCode;

    /**
     * The transfer reason
     *
     * @var string
     */
    protected $disburseReason;

    /**
     * The amount to transfer
     *
     * @var string
     */
    protected $disburseAmount;

    /**
     * The type of transaction to log to the transaction table
     *
     * @var string
     */
    protected $transactionType;

    /**
     * Foreign key for transaction table
     *
     * @var string
     */
    protected $transactionForeignKey;

    /**
     * @deprecated
     * Foreign key for pending disbursal table
     *
     * @var string
     */
    protected $pendingDisbursalForeignKey;

    /**
     * Pending disbursal type
     *
     * @var string
     */
    protected $disbursalType;

    /**
     * Transaction Handle
     *
     * @var Transaction
     */
    protected $transaction;

    /**
     * The state before the disbursal is done
     * @var string
     */
    protected $beforeDisbursalState = 'pending';

    /**
     * The state after the disbursal is done
     * @var string
     */
    protected $afterDisbursalState = 'success';

    /**
     * Check if this entity can disburse
     *
     * @return bool
     */
    protected function canDisburse(): bool
    {
        return !$this->hasPendingDisbursal();
    }

    /**
     * Get the bank information to disburse to
     */
    protected function getBank()
    {
        return $this->bank ?? null;
    }

    /**
     * Get the model table for this model
     * Default to native getTable
     */
    protected function getModelTable()
    {
        return $this->getTable();
    }

    /**
     * Set the transfer parameters on each implementation
     * Sample below is for expenses
     *
     * @return void
     */
    protected function setTransferParameters(): void
    {

        $this->disburseReason = 'Customer Loan Disbursal #' . $this->id;
        $this->transactionType = 'loan_disbursal';
        $this->transactionForeignKey = 'loan_id';
        $this->disbursalType = 'loan_disbursal';
    }

    /**
     * Create default transfer parameters
     * and merge it with setTransferParameters()
     */
    private function mergeTransferParameters()
    {
        $this->processorSecretKey = config('paystack.accounts.default.secret_key', null);
        $this->recipientCode = $this->getBank()->getRecipientCode();
        $this->disburseAmount = $this->amount;

        $this->setTransferParameters();

    }

    /**
     * Called before disbursal is done
     * Useful for setting value
     *
     * @return void
     */
    protected function beforeDisburse(): void
    {
        return;
    }

    /**
     * Called after disbursal is done, success or failure
     * Useful for setting value
     *
     * @return void
     */
    protected function afterDisburse(): void
    {
        return;
    }

    /**
     * Called when there is a disburse success
     * Useful for setting value
     *
     * @return void
     */
    protected function disburseSuccess(): void
    {
        return;
    }

    /**
     * Called when disburse has been attempted and it is pending
     * Useful for setting value
     *
     * @return void
     */
    protected function onPendingDisbursal(): void
    {
        return;
    }

    /**
     * Called when transaction is generted before main disbursal attempt
     * Useful for setting value
     *
     * @return void
     */
    protected function onTransactionGeneration(Transaction $txn): void
    {
        return;
    }

    /**
     * Attempt to disburse the expense
     *
     * @return void
     */
    public function attemptDisburse(): void
    {

        if (!$this->canDisburse()) {
            return;
        }

        $this->mergeTransferParameters();
        $this->beforeDisburse();

        $processorSecretKey = $this->processorSecretKey;

        $papi = new PaystackApi();

        if ($processorSecretKey) {
            $papi->setSecretKey($processorSecretKey);
        }

        $recipient_code = $this->recipientCode;

        if (!$recipient_code) {
            throw new \Exception('Reciepient not provided');
        }

        $cReason = $this->disburseReason ?? 'Disbursal';

        $data = [
            'source' => 'balance',
            'reason' => $cReason,
            'amount' => $this->disburseAmount * 100,
            'recipient' => $recipient_code,
        ];
        $payload = (Object) $data;
        $payload->disbursal_id = $this->id;
        $payload->disbursal_type = $this->disbursalType;

        $this->transaction = Transaction::initialize($this->disburseAmount, $payload);
        $this->transaction->type = $this->transactionType;
        $this->transaction->description = $cReason;
        $this->transaction->disbursal_type = $this->disbursalType;
        $this->transaction->entity_id = $this->id;

        if ($this->transactionForeignKey) {
            $this->transaction->{$this->transactionForeignKey} = $this->id;
        }

        $this->transaction->save();

        $data['reference'] = $this->transaction->reference;
        // dd($data);

        $this->onTransactionGeneration($this->transaction);

        $result = $papi->send("/transfer", $data);

        // Log::debug($result);
        $result = json_decode($result);

        if (isset($result->status) && isset($result->data)) {

            $this->processDisburseResponse($result);
            $this->afterDisburse();

        } else {

            $message = "Disbursal unsuccessful";
            Log::debug(json_encode($result));

            if (isset($result->message) && $result->message != "") {
                $message = $result->message;
            }
            throw new \Exception($message);
        }

    }

    /**
     * Process gateway response
     *
     * @param Object $result                    The gateway response
     * @param bool   $logPendingDisbursal       Log back a pending disbursal if transaction cannot be confirmed,
     *                                          use this if you are not verifying a pending disbursal
     * @return void
     */
    public function processDisburseResponse($result)
    {

        if (!$this->transaction) {
            $this->transaction = Transaction::where('reference', $result->data->reference)->first();
        }

        // Wierd
        if (!$this->transaction) {
            return;
        }

        if ($result->data->status == 'success') {

            try {

                $this->transaction->status = 'success';
                $this->transaction->paid_at = now();
                $this->transaction->currency = 'NGN';
                $this->transaction->ip_address = Helper::getIp();
                $this->transaction->save();
                $this->disburseSuccess();

            } catch (\Throwable $th) {
                //throw $th;
            }

        } else {
            $this->onPendingDisbursal();
        }

    }

    /**
     * Attempt to confirm a pending disbursal
     *
     * @param string $transferCode
     *
     * @return bool
     */
    public function attemptConfirm(string $transferCode): void
    {

        $this->mergeTransferParameters();

        $papi = new PaystackApi();
        if ($this->processorSecretKey) {
            $papi->setSecretKey($this->processorSecretKey);
        }

        $result = $papi->send("/transfer/" . $transferCode);

        $result = json_decode($result);

        if (!$result) {
            return;
        }

        $this->processDisburseResponse($result, false);
    }

    /**
     * Check if this entity has a pending disbursal
     */
    public function hasPendingDisbursal(): bool
    {
        // return !!(Transaction::where([
        //     'status' => 'pending',
        //     'type' => $this->transactionType,
        //     'disbursal_type' => $this->disbursalType,
        //     'entity_id' => $this->id,
        // ])->count());

        $disbursal = $this->getDisbursalTransaction();
        return $disbursal && $disbursal->status === 'pending';
    }

    /**
     * Get the disbursal transaction
     */
    public function getDisbursalTransaction(): ?Transaction
    {
        return Transaction::where([
            'type' => $this->transactionType,
            'disbursal_type' => $this->disbursalType,
            'entity_id' => $this->id,
        ])->first();

    }

    private function updateDb(array $data)
    {
        try {
            DB::table($this->getModelTable())->where('id', $this->id)
                ->update($data);
            foreach ($data as $d => $v) {
                $this->{$d} = $v;
            }
        } catch (\Throwable $th) {
            //shit happens
        }

    }

    /**
     * Attenpt to rollback a disbursal
     */
    public function rollbackDisbursal()
    {
        // Revert back to pending and
        // make sure it doesnt call
        // any on update observers
        $this->updateDb([
            'status' => $this->beforeDisbursalState,
        ]);

        // Try to update other things
        $this->updateDb([
            'pending_disbursal' => false,
        ]);

    }

}
