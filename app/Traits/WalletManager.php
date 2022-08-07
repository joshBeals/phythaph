<?php
namespace App\Traits;

use App\Exceptions\InsufficientFundException;
use App\Exceptions\NotAWalletTypeException;
use App\Models\Transaction;
use App\Models\UserWallet;
use App\Models\UserWalletBalanceHistory;
use Illuminate\Support\Facades\DB;
use \App\Models\User;

/**
 * Manage wallets of different account type be it investment or savings
 * it works directly with the User, UserWallet and UserWalletBalanceHistory models
 *
 * This is the proceed of savings and investments
 */
trait WalletManager
{

    /**
     * @var $wallet Cache the wallet object
     */
    private $wallet;

    /**
     * If the wallet names can be flexible,
     * not hard coded into the wallet model
     *
     * @return bool
     */
    protected function isFlexibleWallet()
    {
        return true;
    }

    /**
     * Get the user this wallet belongs to
     *
     * @return \App\User
     */
    protected function getWalletUser(): User
    {
        return $this;
    }

    /**
     * Add money to a specific wallet
     * Please note that this is not add money to investment or savings,
     * It is about the proceeds of investment or savings
     *
     * @param float $amount                     Amount to add|remove in naira
     * @param string|null $description          Description of operation
     * @param Transaction|null $txn      Transaction for the operations
     *
     * @return float                            The new balance
     *
     */
    public function depositToWallet(
        float $amount,
        string $description = null,
        Transaction $txn = null
    ): float {

        return $this->alterWalletBalance(
            $amount,
            UserWallet::ACCOUNT_OPERATIONS[0],
            $txn,
            $description
        );
    }

    /**
     * Check if the user has this wallet
     *
     * @return bool
     */
    public function hasWallet(): bool
    {
        return $this->getWallet() ? true : false;
    }

    /**
     * Get the wallet id of this wallet
     */
    private function getWallet(): ?UserWallet
    {
        // If the wallet id is previously set and is not set to null;
        if ($this->wallet) {
            return $this->wallet;
        }

        $this->wallet = UserWallet::where(
            'account_type', $this->getWalletName()
        )->where('user_id', $this->getWalletUser()->id)->first();

        return $this->wallet;
    }

    /**
     * The direct reverse of deposit to wallet
     * @param float $amount                     Amount to add|remove in naira
     * @param string|null $description          Description of operation
     * @param Transaction|null $txn      Transaction for the operations
     *
     * @return float                            The new balance
     *
     */
    public function withdrawFromWallet(
        float $amount,
        string $description = null,
        Transaction $txn = null
    ): float {

        return $this->alterWalletBalance(
            $amount,
            UserWallet::ACCOUNT_OPERATIONS[1],
            $txn,
            $description
        );
    }

    /**
     * Get the wallet account type for this object
     *
     * @return string   The wallet account_type
     */
    public function getWalletName(): string
    {
        return $this->accountType ?? 'ngn';
    }

    /**
     * Get the ID representation of the entity that own the wallet
     *
     * @return int
     */
    protected function getEntityId(): ?int
    {
        return $this->id ?? null;
    }

    /**
     * Alter the user balance for a specific wallet, balance in kobo
     *
     * @param float $amount                     Amount to add|remove in naira
     * @param string $operation                 deposit or withdrawal
     * @param Transaction|null $txn      Transaction for the operations
     * @param string|null $description          Description of operation
     *
     * @throws NotAWalletTypeException         Specified $accountType does not exist
     * @throws InsufficientFundException        Insufficient Fund to withdraw
     * @return float                            The new balance
     */
    private function alterWalletBalance(
        float $amount,
        string $operation = 'deposit',
        Transaction $txn = null,
        string $description = null
    ): float {
        DB::beginTransaction();

        $accountType = $this->getWalletName();

        try {

            if (!$this->isFlexibleWallet() && !in_array($accountType, UserWallet::ACCOUNT_TYPES)) {
                throw new NotAWalletTypeException;
            }

            $hasWallet = $this->hasWallet();
            $amount = round($amount * 100, 4);

            $user = $this->getWalletUser();

            if ($hasWallet) {
                $wallet = UserWallet::where('account_type', $accountType)->where('user_id', $user->id)->lockForUpdate()->first();
            } else {
                $wallet = new UserWallet;
                $wallet->user_id = $user->id;
                $wallet->account_type = $accountType;
                $wallet->balance = 0;
            }

            if ($operation === UserWallet::ACCOUNT_OPERATIONS[1] && $wallet->balance < $amount) {
                throw new InsufficientFundException;
            }

            switch ($operation) {
                case UserWallet::ACCOUNT_OPERATIONS[0]:
                    $wallet->balance += $amount;
                    $wallet->last_deposit_at = now();
                    break;
                case UserWallet::ACCOUNT_OPERATIONS[1]:
                    $wallet->balance -= $amount;
                    $wallet->last_withdrawal_at = now();
                    break;
            }

            $wallet->save();

            $desc = strtoupper($accountType) . "_WALLET";
            $theENtity = $this->getEntityId();
            if ($theENtity) {
                $desc .= "_" . $theENtity;
            }

            $desc .= "_" . strtoupper($operation);

            if ($description) {
                $desc .= '/' . $description;
            }

            if ($txn) {
                $desc .= '/' . $txn->reference;
            }

            // Register history
            UserWalletBalanceHistory::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => $operation,
                'amount' => $amount,
                'description' => $desc,
                'transaction_id' => $txn ? $txn->id : null,
            ]);

            DB::commit();

            return $wallet->balance;

        } catch (\Throwable $th) {
            DB::rollBack();
            // Rethrow
            throw $th;
        }
    }

    /**
     * Get the total of a perticular transaction operation in Kobo
     *
     * @param string $type  The operation type like 'deposit', 'withdrawal', 'others'\
     *
     * @return float
     */
    private function getTotalByType(string $type): float
    {

        $wallet = $this->getWallet();

        // If the user does not have this wallet
        if (!$wallet) {
            return 0;
        }

        $walletId = $wallet->id;

        $historyTotal = UserWalletBalanceHistory::where([
            'user_id' => $this->getWalletUser()->id,
            'type' => $type,
            'wallet_id' => $walletId,
        ])->sum('amount');

        return $historyTotal ? $historyTotal / 100 : 0;
    }

    /**
     * Get the total deposit on this wallet
     * using a sum of the deposit history
     *
     * @return float
     */
    public function getTotalDeposit(): float
    {
        return $this->getTotalByType(UserWallet::ACCOUNT_OPERATIONS[0]);
    }

    /**
     * Get the total withdrawal on this wallet
     * using a sum of the deposit history
     *
     * @return float
     */
    public function getTotalWithdrawal(): float
    {
        return $this->getTotalByType(UserWallet::ACCOUNT_OPERATIONS[1]);
    }

    /**
     * Get the balance on this wallet
     */
    public function getWalletBalance(): float
    {
        return UserWallet::getWalletBalaceForUser($this->getWalletName(), $this->getWalletUser());
    }
}
