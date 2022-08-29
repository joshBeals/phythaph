<?php
namespace App\Classes;

use GuzzleHttp\Client;
use Unicodeveloper\Paystack\Paystack as PaystackBase;

class Paystack extends PaystackBase
{
    /**
     * Set the secret key for the request
     * This replaces setKay function on parent
     *
     * @param string $key The key to set
     *
     * @return Self
     */
    public function setSecretKey(string $key): Self
    {
        $this->secretKey = $key;
        $this->modifyClient();

        return $this;
    }

    /**
     * Modify the cloent of the request
     * This replaces setRequestOptions method on the parent
     *
     * @return Self
     */
    protected function modifyClient(): Self
    {
        $authBearer = 'Bearer ' . $this->secretKey;

        $this->client = new Client(
            [
                'base_uri' => $this->baseUrl,
                'headers' => [
                    'Authorization' => $authBearer,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]
        );

        return $this;
    }

    /**
     * Hit Paystack Gateway to Verify that the transaction is valid
     * The method replaces the verifyTransactionAtGateway
     */
    protected function verifyTransaction()
    {
        $transactionRef = request()->query('trxref');

        $getTemp = \App\TemporaryTransaction::where('reference', $transactionRef)->first();

        if ($getTemp && $getTemp->account) {
            $this->setSecretKey(config($getTemp->account));
        }

        $relativeUrl = "/transaction/verify/{$transactionRef}";

        $this->response = $this->client->get($this->baseUrl . $relativeUrl, []);
    }

    /**
     * True or false condition whether the transaction is verified
     * @return boolean
     */
    public function isTransactionVerificationValid()
    {
        try {
            //code...
            $this->verifyTransaction();
            $result = $this->getResponse()['message'];

            switch ($result) {
                case self::VS:
                    $validate = true;
                    break;
                case self::ITF:
                    $validate = false;
                    break;
                default:
                    $validate = false;
                    break;
            }

            return $validate;
        } catch (\Throwable $th) {
            return false;
        }

    }

    /**
     * Get the whole response from a get operation
     * @return array
     */
    protected function getResponse()
    {
        return json_decode($this->response->getBody(), true);
    }
}
