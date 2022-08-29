<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Classes\Helper;
use App\Classes\Paystack;
use App\Classes\PaystackApi;
use App\Models\Loan;
use App\Models\Savings\UserSaving;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @group Payment APIs
 */
class PaymentController extends Controller
{

    private $paymentDetails;
    private $transaction;
    private $meta;
    private $user;

    private function throwForbidden()
    {
        $forbid = [
            '403 Forbidden: oh yea and you can go on and on and on, we will not burge',
            'Eish! 🔥😈😂 ',
        ];

        return response($forbid, 403);

    }

    /**
     *@bodyParam amount string required
     *@bodyParam type string required
     *@bodyParam scope string required
     *@bodyParam description string required
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "Transaction generated",
     *    }
     *@response status=400 scenario="Bad request" {
     *    "success": false,
     *    "message": "Cannot initialize transaction, please check your input and try again"
     *  }
     */
    public function initializeTransaction(Request $request)
    {

        $amount = $request->input('amount');
        $description = $request->input('description');
        $type = $request->input('type') ?? $request->input('scope') ?? null;

        if (!$amount) {
            return Helper::apiFail("Amount is required to initialize a transaction");
        }

        $txn = Transaction::initialize(floatval($amount), (Object) $request->all());

        // Just user only
        $user = Auth::guard('web')->user();
        if (!$user) {
            $user = Auth::guard('api')->user();
        }
        if ($user) {
            $txn->user_id = $user->id;
        }

        if ($description) {
            $txn->description = $description ?? "Customer Payment";
        }

        if ($type) {
            $txn->type = $type;
        }

        if (!$txn->save()) {
            return Helper::apiFail('Cannot initialize transaction, please check your input and try again');
        }

        return Helper::apiSuccess($txn, 'Transaction generated');

    }

    /**
     * Obtain Paystack payment information
     * @return void
     */
    public function handleGatewayCallback(Request $request)
    {
        try {
            $ps = new Paystack;
            $pd = $ps->getPaymentData();
        } catch (\Throwable $e) {
            return view('errors.transaction_failed');
        }

        return $this->process($pd);

    }

    /**
     * Handle Incoming hook
     */
    public function handleWebhook(Request $request)
    {

        if (!$request->headers->has('x-paystack-signature')) {
            return $this->throwForbidden();
        }

        $knownSignatures = [
            \hash_hmac('sha512', $request->getContent(), config('paystack.secretKey')),
        ];

        foreach (config("paystack.accounts") as $account) {
            if (is_array($account) && array_key_exists('secret_key', $account)) {
                array_push(
                    $knownSignatures,
                    \hash_hmac('sha512', $request->getContent(), $account['secret_key'])
                );
            }
        }

        // Confirm header
        if (!in_array($request->headers->get('x-paystack-signature'), $knownSignatures)) {
            return $this->throwForbidden();
        }

        $paymentDetails = Helper::arrayToObject($request->all());
        $this->paymentDetails = $paymentDetails;
        if (config('app.env') != 'production') {
            Log::debug($request->all());
        }

        if (!isset($paymentDetails->event) || !isset($paymentDetails->data) || !isset($paymentDetails->data->reference)) {
            return response(null, 400);
        }

        \http_response_code(200);

        $this->transaction = Transaction::where('reference', $paymentDetails->data->reference)->first();
        if (config('app.env') != 'production') {
            Log::debug($this->transaction ?? "No transaction found for reference {$paymentDetails->data->reference}");
        }

        if ($this->transaction) {
            $this->transaction->paid_via = "processor.paystack";
            $this->transaction->save();
        }

        switch ($paymentDetails->event) {
            case "charge.success":
            case "charge.failed":
                // If it is a failed transaction, update status to failed
                if ($paymentDetails->data->status !== 'success') {
                    if ($this->transaction) {
                        $this->transaction->status = $paymentDetails->data->status ?? 'failed';
                        $this->transaction->save();
                    }
                    return;
                }

                $this->process();
                break;
            case "transfer.success":
            case "transfer.failed":
                if ($this->transaction) {
                    $this->transaction->processtransfer($paymentDetails);
                }
                break;
        }

    }

    /**
     * Process the callback from webhook or paystack redirect
     *
     * @param object $paymentDetails   The detailed response from paystack
     *
     * @return mixed
     */
    public function process()
    {

        $paymentDetails = $this->paymentDetails;

        $meta = $paymentDetails->data->metadata ?? $paymentDetails->data->meta ?? null;
        if (!$meta) {
            $meta = \json_decode($this->transaction->payload);
        }

        if (!$meta) {
            return;
        }

        $this->meta = $meta;

        $description = $meta->description ?? null;
        $scope = $meta->scope ?? $this->transaction->type ?? null;
        if (!$scope) {
            return;
        }

        $transaction_id = $meta->transaction_id ?? $this->transaction->id ?? null;
        $reference = $paymentDetails->data->reference;
        $user_id = $meta->user_id ?? $this->transaction->user_id;

        if (!$user_id) {
            return;
        }

        $this->user = User::find($user_id);

        $txn = $this->transaction;

        // If paystack generated the transaction reference
        // New bug they need to fix
        if (!$this->transaction && $transaction_id) {
            try {
                $this->transaction = Transaction::find(intval($transaction_id));

                if ($this->transaction) {
                    $this->transaction->reference = $reference;
                    $this->transaction->paid_via = "processor.paystack";
                    $this->transaction->save();
                }
            } catch (\Throwable $th) {
                //throw $th;
            }

        }

        // Still no transaction or the said transaction is already processed
        if (!$this->transaction || $this->transaction->status !== 'pending') {
            return;
        }

        $this->transaction->paid_at = isset($paymentDetails->data->transaction_date) ? Carbon::parse($paymentDetails->data->transaction_date) : now();
        $this->transaction->ip_address = $paymentDetails->data->ip_address ?? Helper::getIp();
        $this->transaction->description = $description;
        $this->transaction->customer_code = $paymentDetails->data->customer->customer_code ?? null;
        $this->transaction->payment_method = 'processor';

        if ($scope) {

            $handler = 'handle' . studly_case($scope);

            try {
                $this->{$handler}();

            } catch (\Throwable $th) {
                throw $th;
            }
        }

        $this->transaction->description = $meta->description ?? "";
        $this->transaction->status = 'success';
        $this->transaction->save();

    }

    public function handleAddCard()
    {
        $paymentDetails = $this->paymentDetails;
        $txn = $this->transaction;
        $meta = $this->meta;

        $user_id = $meta->user_id ?? null;
        if (!$user_id) {
            return;
        }

        $card = $this->addCardFromPayment($user_id);

        if ($card) {
            $txn->card_id = $card->id;
        }

    }

    /**
     * Payment that has no transaction history recorded after at process
     * must be from a payment page, We handle it here
     *
     * @param object $paymentDetails
     *
     * @return void
     */
    public function handlePaymentPage()
    {
        $paymentDetails = $this->paymentDetails;

        $email = $paymentDetails->data->customer->email;
        $amount = $paymentDetails->data->amount;
        $txnRef = $paymentDetails->data->reference;
        $meta = $this->meta;

        // This is the most important part
        $loan = null;

        if ($meta && isset($meta->custom_fields)) {
            foreach ($meta->custom_fields as $m) {

                if (isset($m->variable_name)
                    && $m->variable_name === 'loan_id'
                    && isset($m->value)) {
                    $loan = Loan::isRunning()->where('id', $m->value)->first();
                }

            }
        }

        // Cannot figure out loan from metadata
        if (!$loan) {
            if (!$email || !$amount || !$txnRef) {
                return $this->recordUnknownPayment();
            }

            // First get the user with the email address
            $user = User::where('email', trim($email))->first();

            // If the user does not exist, log the transaction reference,
            // email, amount and timepaid and complete payload into the databaseand return
            if (!$user) {
                return $this->recordUnknownPayment($paymentDetails);
            }

            // Get get the first loan with said user
            $loan = Loan::isRunning()->where('user_id', $user->id)->first();
        }

        // Still no loan do the same as above
        if (!$loan) {
            $this->recordUnknownPayment($paymentDetails);
        }

        // Store the card against the user
        if ($paymentDetails->data->channel === "card") {
            $card = $this->addCardFromPayment($user->id, true);
        }

        // Record the payment on the loan
        return $loan->recordPayment(
            $paymentDetails->data->amount / 100,
            $paymentDetails->data->reference,
            null,
            'processor',
            $paymentDetails->data->customer->customer_code,
            isset($paymentDetails->data->fees) ? $paymentDetails->data->fees / 100 : null
        );

    }

    public function handleWalletTopup()
    {
        $paymentDetails = $this->paymentDetails;
        $data = $paymentDetails->data;
        $txn = $this->transaction;
        $meta = $this->meta;
        $description = $meta->description ?? null;

        $user_id = $meta->user_id ?? null;

        $user = $this->user;

        if (!$user) {
            return;
        }

        $user->depositToWallet(floatval($txn->amount / 100), $description, $txn);

    }

    public function handleMembershipSubscription()
    {
        $paymentDetails = $this->paymentDetails;
        $data = $paymentDetails->data;
        $txn = $this->transaction;
        $meta = $this->meta;
        $description = $meta->description ?? null;

        $user_id = $meta->user_id ?? null;

        $user = $this->user;

        if (!$user) {
            return;
        }

        $user->processSubscriptionPayment($paymentDetails, $txn);

    }

    public function handleUserPlanChange()
    {
        $paymentDetails = $this->paymentDetails;
        $data = $paymentDetails->data;
        $txn = $this->transaction;
        $meta = $this->meta;
        $description = $meta->description ?? null;

        $user_id = $meta->user_id ?? null;

        $user = $this->user;

        if (!$user) {
            return;
        }

        $user->processSubscriptionPayment($paymentDetails, $txn);

    }

    public function handleInvestmentPurchase()
    {
        $paymentDetails = $this->paymentDetails;
        $data = $paymentDetails->data;
        $txn = $this->transaction;
        $meta = $this->meta;

        $user_id = $meta->user_id ?? null;

        $user = $this->user;

        if (!$user) {
            return;
        }

        $investment = $meta->investment_id ?? null;

        if (!$investment) {
            return;
        }

        $investment = \App\Models\Investment::find(intval($investment));

        if (!$investment) {
            return;
        }

        \App\Models\UserInvestment::createInvestment(
            $investment, $txn, $meta
        );

        // $userInv = new \App\Models\UserInvestment;
        // $userInv->user_id = $user_id;
        // $userInv->investment_id = $meta->investment_id;

        // $userInv->tenor = intval($meta->tenor);
        // $userInv->rate = doubleval($meta->rate);
        // $userInv->unit_price = doubleval($meta->unit_price);
        // $userInv->unit_purchased = intval($meta->unit_purchased);
        // $userInv->investment_total = doubleval($paymentDetails->data->amount);
        // $userInv->interest_calculation = $meta->interest_calculation;
        // $userInv->currency_id = $meta->currency_id;

        // $userInv->invested_at = now();
        // $tenorUnit = Helper::getTenorUnit($meta->interest_calculation);
        // $unitDate = "add" . ucwords($tenorUnit);
        // $userInv->mature_at = (clone ($userInv->invested_at))->{$unitDate}($userInv->tenor);

        // $userInv->transaction_id = $txn->id;
        // $userInv->status = 'active';

        // $userInv->save();

    }

    public function handleRealEstatePurchase()
    {
        $paymentDetails = $this->paymentDetails;
        $data = $paymentDetails->data;
        $txn = $this->transaction;
        $meta = $this->meta;

        $user_id = $meta->user_id ?? null;

        $user = $this->user;

        if (!$user) {
            return;
        }

        $estate = $meta->estate_id;

        if (!$estate) {
            return;
        }

        $estate = \App\Models\Estate::find(intval($estate));

        if (!$estate) {
            return;
        }

        $meta->sale_init_at = $txn->paid_at;
        $meta->investors = [
            [
                'user_id' => $user->id,
                'slots' => $meta->unit_purchased,
                'transaction_id' => $txn->id,
                'paid_at' => $txn->paid_at,
            ],
        ];

        \App\Classes\RealEstateManager::createFromInputs($meta);
    }

    /**
     * Add card to a user from payment information
     *
     * @param object $paymentDetails
     *
     * @return void
     */
    public function addCardFromPayment($user_id, $hidden = false)
    {
        $paymentDetails = $this->paymentDetails;

        // More fields are queried to be sure
        $card = UserCard::where('user_id', $user_id)
            ->where('last4', $paymentDetails->data->authorization->last4)
            ->where('exp_month', $paymentDetails->data->authorization->exp_month)
            ->where('exp_year', $paymentDetails->data->authorization->exp_year)
            ->first();

        if ($card) {
            return $card;
        }

        // if the card does not exist, create it.
        $card = new UserCard;

        $card->user_id = $user_id;

        $cardDetail = [
            "authorization_code",
            "bin",
            "last4",
            "exp_month",
            "exp_year",
            "channel",
            "card_type",
            "bank",
            "country_code",
            "brand",
            "reusable",
            "signature",
        ];

        // foreach ($paymentDetails->data->authorization as $key => $value) {
        foreach ($cardDetail as $key) {

            try {
                $card->{$key} = $paymentDetails->data->authorization->{$key};
            } catch (\Throwable $th) {
                //throw $th;
            }

        }
        $card->hidden = $hidden;

        if (!$hidden) {
            // If we are linking card, make the card default
            UserCard::where('user_id', $user_id)
                ->update(['is_default' => 0]);
            $card->is_default = true;
        }

        if (!$card->save()) {
            abort(403, 'Unauthorized action.');
        }

        return $card;
    }

    public static function recordUnknownPayment($paymentDetails)
    {
        // For now just log return
        Log::debug('Unknown payment');
    }

    /**
     * Handle request sent to paystack
     *
     * @bodyParam endpoint string required
     */
    public function paystackApi(Request $request)
    {

        $endpoint = $request->input('endpoint');

        if (!$endpoint) {
            // abort(401, "Endpoint is required");
            return Helper::apiFail("Please provide endpoint");
        }

        /**
         * BVN Shim
         */

        $bvnEndpoint = \preg_replace("/^\//", "", $endpoint);

        $user = $request->user();
        if (!$user) {
            $user = new \StdClass;
            $user->first_name = " ";
            $user->last_name = " ";
        }

        if (starts_with($bvnEndpoint, "bank/resolve_bvn")) {
            // dd("na bvn");
            return [
                'status' => true,
                'message' => 'BVN number will be verified',
                'data' => [
                    'first_name' => strtoupper($user->first_name),
                    'last_name' => strtoupper($user->last_name),
                ],
            ];
        }

        /**
         * End of BVN Shim
         */

        $papi = new PaystackApi();
        // if (str_contains($endpoint, 'resolve_bvn')) {
        // $channel = config('paystack.accounts.' . setting('site.fasttract_disburse_account', 'fasttrack'));
        // if (isset($channel) && is_array($channel)) {
        // $papi->setSecretKey($channel['secret_key']);
        // }
        // }
        $response = $papi->send($endpoint);
        return response()->json(json_decode($response));

    }
    /**
     * Handle phone number verification callback
     */
    public function verifyPhoneCallback(Request $request)
    {
        \http_response_code(200);
        \Illuminate\Support\Facades\Log::debug($request->all());
    }
}
