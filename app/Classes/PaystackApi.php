<?php

namespace App\Classes;

use App\Models\Loan;
use App\Models\Transaction;
use App\User;
use App\UserCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class PaystackApi extends ApiBase
{

    public $secretKey;

    public function __construct()
    {
        $this->baseUrl = Config::get('paystack.paymentUrl');
        $this->setSecretKey(Config::get('paystack.accounts.default.secret_key', false));
        return Parent::__construct();
    }
    /**
     * Set the secret key on the fly
     *
     * @param string $secretKey the secret key #endregion
     *
     * @return Self
     */
    public function setSecretKey(string $key, bool $createClient = true): Self
    {
        $this->secretKey = $key;
        $authBearer = 'Bearer ' . $this->secretKey;
        $this->headers['Authorization'] = $authBearer;

        if ($createClient) {
            $this->createClient();
        }

        return $this;
    }

    /**
     * Reset the Secret key to default set in config
     *
     * @return Self
     */

    public function resetSecretKey()
    {
        return $this->setSecretKey(Config::get('paystack.secretKey', false));
    }

    /**
     * Get the recepient code from a paystack account against a bank account
     *
     * @param array $data       The banking infrormation
     * @param string|null $secretKey    The secret key of the paystack account
     *
     * @return string|null
     */
    public static function getRecipientCode(array $data, ?string $secretKey = null): ?string
    {
        $papi = new Self;
        if ($secretKey) {
            $papi->setSecretKey($secretKey);
        }

        $result = $papi->send("/transferrecipient", $data);

        $result = json_decode($result);

        if ($result->status && isset($result->data->recipient_code) && !is_null($result->data->recipient_code)) {
            return $result->data->recipient_code;
        }

        return null;
    }

    /**
     * Get Balance from paystack in kobo
     *
     * @param string|null $secretKey The secret key of the paystack account
     *
     * @return float
     */
    public static function getBalance($secretKey = null): ?float
    {
        $papi = new Self;
        if ($secretKey) {
            $papi->setSecretKey($secretKey);
        }

        $processorBalance = 0;
        $getBalance = json_decode($papi->send("/balance"));

        if (isset($getBalance->status) && $getBalance->status && isset($getBalance->data) && is_array($getBalance->data)) {
            $processorBalance = $getBalance->data[0]->balance;
        }

        return (float) $processorBalance;
    }

    /**
     * Generate paystack paymnent page url
     *
     * @param array $params     Additional parameter to put into the query string
     * @param array $readonly   Parameter to set as readonly
     *
     */
    public static function generatePaymentPageUrl(array $params = [], array $readonly = [], bool $shortLink = true): string
    {
        $readonly = sizeOf($readonly) ? ["readonly" => implode(",", $readonly)] : $readonly;
        $params = array_merge($params, $readonly);

        $base = config('paystack.payment_page_url');

        $link = $base . (sizeOf($params) ? "?" . http_build_query($params) : "");

        if ($shortLink) {
            return GoogleDynamicLink::make($link);
        }

        return $link;
    }

}
