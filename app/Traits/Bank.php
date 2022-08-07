<?php
namespace App\Traits;

use App\Classes\Helper;
use App\Classes\PaystackApi;
use Illuminate\Support\Facades\Log;

trait Bank
{

    // protected $paystackSecretKey = null;
    protected $paystackAccount = 'default';
    protected $entityDescription;
    protected $entityRelationshipField;
    protected $entityRelationshipNameField = 'name';

    /**
     * Get the name to identity the object
     * with when getting reciepient code from paystack
     *
     * @return string
     */
    protected function getEntityName(): string
    {
        return $this->{strtolower($this->entityDescription)}->{$this->entityRelationshipNameField};
    }

    /**
     * Get this admin recipient code from database or paystack
     *
     * @return string
     */
    public function getRecipientCode(): ?string
    {
        // if ($this->recipient_code) {
        //     return $this->recipient_code;
        // }

        $rc = UserBankRecipientCode::where([
            'bank_id' => $this->id,
            'account' => $this->paystackAccount,
        ]);

        return $this->requestRecepientCode();
    }

    /**
     * Get the reciepient code from paystack
     *
     * @return string
     */
    public function requestRecepientCode(): ?string
    {
        $paystackSecretKey = config('paystack.accounts')[$this->paystackAccount]['secret_key'];

        $rdata = [
            'type' => 'nuban',
            'name' => strtoupper($this->getEntityName()),
            'description' => config('app.name') . $this->entityDescription,
            'account_number' => $this->account_number,
            'bank_code' => $this->bank_code,
            'currency' => 'NGN',
        ];

        $recipient_code = PaystackApi::getRecipientCode($rdata, $paystackSecretKey);

        if ($recipient_code) {
            $this->recipient_code = $recipient_code;
            $this->save();

            return $recipient_code;
        } else {
            return null;
        }
    }

    /**
     * Get the full information of the bank in non model form
     *
     * @return StdClass
     */
    public function getInfo(): \StdClass
    {
        return json_decode(json_encode($this));
    }

    /**
     * This is more like a proxy to Model::create only that
     * it figures out the relationship information from $this->entityRelationshipField
     *
     * @param int $entityId     The id of the entity to add
     * @param array $data       Then bank information data
     *
     * @return Self
     */
    public static function addEntityBank(int $entityId, array $data): ?Self
    {
        if (!$entityId) {
            return null;
        }

        $bank = new Self;
        $bank->{$bank->entityRelationshipField} = $entityId;

        // Just to be sure
        unset($data['entity_id']);

        foreach ($data as $k => $v) {
            $bank->{$k} = $v;
        }

        $bank->save();

        return $bank;
    }

    /**
     * Get the list of banks from paystack
     */
    public static function getCachedBankList()
    {
        return cache()->remember('paystack_banks', 60 * 60 * 24 * 30, function () {
            return Self::getBankList();
        });
    }

    /**
     * Get the list of banks from paystack
     */
    public static function getBankList()
    {
        $papi = new PaystackApi();
        $result = $papi->send('/bank/');
        $result = json_decode($result);

        if ($result && isset($result->status) && $result->status) {
            return $result->data;
        }

        return [];

    }
}
