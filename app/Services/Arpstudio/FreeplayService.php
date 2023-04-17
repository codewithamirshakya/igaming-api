<?php

namespace App\Services\Arpstudio;

use App\Helpers\Utility;
use App\Models\Account;
use App\Services\Opapi\OpapiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FreeplayService
{
    use Utility;

    public $baseUrl;
    public $username;
    public $password;
    public $defaultAuthorizer;
    public $opmgAuthorizer;
    public $studioAuthorizer;
    public $pesoInquiry;
    public $pesoAdjustment;
    public $nccuInquiry;
    public $nccuAdjustment;
    public $accountNumber;
    public $amount;
    public $promotionType;
    public $transaction_id;
    public $transaction_status;
    public $before_balance;
    public $after_balance;
    public $promo_before_balance; // = before_balance
    public $promo_after_balance; // = after_balance
    public $provider_before_balance; // = ref_before_balance
    public $provider_after_balance; // = ref_after_balance
    public $source_type, $ref_source_type; //Exp: Wallet, Platform
    public $source_id, $ref_source_id; // Exp: if source_type = wallet - id = 1 is ACSC
    public $transaction_type;
    public $asset_id; // For ARPS MCID or OPMG Table ID or etc
    public $provider;
    public $clientip;
    public $log_category = 'freeplay'; // Log category
    public $bp_balance;
    public $sp_balance;

    public function init()
    {
        $solaire                    = config('params.endpoint.solaire');
        $this->baseUrl              = $solaire['config']['base_url'];
        $this->username             = $solaire['config']['username'];
        $this->password             = $solaire['config']['password'];
        $this->defaultAuthorizer    = $solaire['config']['default_authorizer'];
        $this->opmgAuthorizer       = $solaire['config']['opmg_authorizer'];
        $this->studioAuthorizer     = $solaire['config']['studio_authorizer'];

        $this->pesoAdjustment       = $solaire['url']['peso_adjustment'];
        $this->nccuInquiry          = $solaire['url']['nccu_inquiry'];
        $this->pesoInquiry          = $solaire['url']['peso_inquiry'];
        $this->nccuAdjustment       = $solaire['url']['nccu_adjustment'];
    }

    public function makeRequest($url, $method, $params)
    {
        return Http::post($url, $params);
    }

    /**
    * Get ACSC Promo Credits balance - Bonus Peso and Solaire Peso
    *
    * @param mixed $account_number
    *
    * @return array result
    */
    public function pesoInquiry()
    {
        $start = microtime(true);
        $opapi = new OpapiService();
        //Validate required param = account_number
        // $this->validateScenario('inquiry');
        $retry_limit = 5;  // Set maximum number of retries
        $retry_count = 0;  // Initialize retry counter

        try {
            $data = ['username' => $this->accountNumber];

            while ($retry_count < $retry_limit) {
                $result = $opapi->opApi( 'freeplay_get_balance.php', $data );
                $result = json_decode($result, true);
                if (isset($result['status']) && $result['status'] == false && isset($result['retry']) && $result['retry'] == true) {
                    $retry_count++;
                    continue;
                } else {
                    break;
                }
            }
            $response['retry_count'] = $retry_count;
            if ($retry_count === $retry_limit) {
                // handle retry limit exceeded error
                $description = 'Unable to connect API. Retry limit exceeded';
                throw new \Exception($description);
            } else {
                // handle response
                if($result != false && isset($result['status']) && $result['status'] === true){
                    $response = $result;
                }else{
                    if($result != false && isset($result['status']) && $result['status'] === false){
                        $description = isset($result['description']) ? $result['description'] : 'Unknown Error';
                    }else{
                        $result = '';
                        $description = 'Unknown Error';
                    }
                    throw new \Exception($description.', Response :'.json_encode($result));
                }
            }
            $end = microtime(true);
            $total_process_time = round($end - $start, 4);

            $response['inquiry_process_time'] = $total_process_time;


        }catch(\Exception $e){
            Log::debug("Patron : " .$this->accountNumber.", Peso Inquiry Error: ".$e->getMessage());
            $response = null;
        }
        //Return Response
        return $response;
    }

    /**
    * Bonus Peso and Solaire Peso adjustment
    *
    * @param mixed $account_number
    * @param string $transaction_type = Deposit/Withdrawal
    * @param string $promotion_type
    * @param integer $amount
    * @param string $provider = arpstudio/opmg/others
    *
    *
    * @return array accountNumber     - Patron account number
    * @return array transactionTime   - The transaction date time
    * @return array transactionStatus - The status of the transaction
    * @return array transactionAmount - The amount for the transaction
    * @return array previousBalance   - The previous amount in ACSC
    * @return array currentBalance    - The current amount in ACSC
    */
    public function pesoAdjustment($transaction_type, $promotion_type, $amount, $provider)
    {
        try{
            $url = $this->pesoAdjustment;
            $method = 'POST';
            Log::debug("Peso Adjustment data: ".$amount." Account Number:". $this->accountNumber);
            //check if not Deposit amount become negative
            $new_amount = ($transaction_type == 'Withdrawal') ? $amount : -$amount;
            if($promotion_type == 'BP'){
                $promotion_type = 'BonusPeso';
            }
            if($promotion_type == 'SP'){
                $promotion_type = 'SolairePeso';
            }

            $authorizer = "";
            switch ($provider){
                case "arpstudio":
                    $authorizer = $this->studioAuthorizer; break;
                case "opmg":
                    $authorizer = $this->opmgAuthorizer; break;
                default:
                    throw new \Exception("Provider is not supported for peso adjustment", 551);
            }
            $request_amount = $new_amount/100; //convert to dollar base on Solaire API needed

            $data = [
                "SolairePesoAndBonusPesoAdjustmentDetailsRequest" => [
                    "patronNumber"      => $this->accountNumber,
                    "transactionAmount" => strval($request_amount), //dollar
                    "balanceType"       => $promotion_type,
                    "comments"          => "Achievement",
                    "authorizer"        => $authorizer,
                    "campaignID"        => "1"
                ]
            ];

            // get previous balance
            $peso_inquiry = $this->pesoInquiry(['account_number' => $this->accountNumber]);
            $raw_previous_balance = (float) str_replace(',', '', $peso_inquiry['solairePesoBonusPesoInquiry']['current'.$promotion_type]);
            $previous_balance = $raw_previous_balance*100;

            $result = $this->makeRequest($url, $method, $data);

            if(isset($result['Body']['responseCode']) && $result['Body']['responseCode'] == '000'){
                if(isset($result['Body']['resultStatus']) && $result['Body']['resultStatus'] == 'SUCCESS' && $result['Body']['currentBalance'] >= 0){
                    $status = 'Success';
                    $current_balance = $result['Body']['currentBalance'];
                }else{
                    //todo - log why it failed, store into transfer log table
                    Log::error("Peso Adjustment failed: " . var_export($result, true));
                    throw new \Exception("Peso adjustment failed: " . var_export($result, true));
                }
            }else{
                $status = 'Failed';
                $current_balance = $previous_balance;
                Log::error("Peso Adjustment failed: " . var_export($result, true));
                throw new \Exception("Peso adjustment failed: " . var_export($result, true));
            }

            $response = [
                'accountNumber'     => $this->accountNumber,
                'transactionTime'   => date('Y-m-d H:i:s'),
                'transactionStatus' => $status,
                'transactionAmount' => strval($amount),
                'previousBalance'   => strval($previous_balance),
                'currentBalance'    => strval($current_balance)
            ];

        }catch(\Exception $e){
            $response = [
                'transactionStatus' => "Failed",
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    /**
    * Get NCCU balance from ACSC
    *
    * @param mixed $account_number
    *
    * @return array result
    */
    public function nccuInquiry()
    {
        //Validate required param = account_number
        //$this->validateScenario('inquiry');
        $status = "Success";

        try {
            $url = $this->nccuInquiry;
            $method = 'GET';
            $data = ['patronNumber' => $this->accountNumber];

            $result = $this->makeRequest($url, $method, $data);

        } catch(\Exception $e) {
            $status = "Failed";
        }

        //Check Response
        if(empty($result)) {
            return null;
        } else {
            return $result;
        }
    }

    /**
    * NCCU adjustment
    *
    * @param mixed $account_number
    * @param string $transaction_type = Deposit/Withdrawal
    * @param integer $amount
    *
    *
    * @return array accountNumber     - Patron account number
    * @return array transactionTime   - The current date time
    * @return array transactionStatus - The status of transaction
    * @return array transactionAmount - The amount for the transaction
    * @return array previousBalance   - The previous amount in ACSC
    * @return array currentBalance    - The current amount in ACSC
    */
    public function nccuAdjustment($account_number, $transaction_type, $amount)
    {
        //Validate required params = ['account_number', 'promotion_type', 'nonCashableCredits'];
        //$this->validateScenario('adjustment');

        try{
            $url = $this->nccuAdjustment;
            $method = 'GET';
            $status = 'Incomplete';

            if($transaction_type == 'Deposit'){
                $transaction_type = 'D';
            }

            if($transaction_type == 'Withdrawal'){
                $transaction_type = 'W';
            }

            $data = [
                "patronNumber"       => $account_number,
                "mode"               => $transaction_type,
                "nonCashableCredits" => strval($amount),
            ];

            $nccu_inquiry = $this->nccuInquiry(['account_number' => $account_number]);
            $previous_balance = $nccu_inquiry['GetNonCashableCreditsResponse']['TotalNonCashableCredits'];

            $result = $this->makeRequest($url, $method, $data);

            if($result['GetPostNonCashableCreditsResponse']['ResponseCode'] == '000'){
                $status = 'Success';
                $current_balance = $result['GetPostNonCashableCreditsResponse']['TotalNonCashableCredits'];
            }else{
                $status = 'Failed';
                $current_balance = $previous_balance;
            }

            $response = [
                'accountNumber'     => $this->accountNumber,
                'transactionTime'   => date('Y-m-d H:i:s'),
                // 'transactionId' => $id,
                'transactionStatus' => $status,
                'transactionAmount' => strval($amount),
                'previousBalance'   => strval($previous_balance),
                'currentBalance'    => strval($current_balance)
            ];

            $params = [
                'account_number'    => $account_number,
                'status'            => $status,
                'promotion_type'    => "NCCU",
                'transaction_type'  => ($transaction_type == 'D') ? 0 : 1,
                'amount'            => $amount,
                'source_type'       => $this->source_type,
                'source_id'         => $this->source_id,
                'before_balance'    => $previous_balance,
                'after_balance'     => $current_balance
            ];

        }catch(\Exception $e){
            $params = [
                'account_number'    => $this->accountNumber,
                'status'            => "failed",
                'promotion_type'    => "NCCU",
                'transaction_type'  => ($transaction_type == 'D') ? 0 : 1,
                'amount'            => $amount,
                'source_type'       => $this->source_type,
                'source_id'         => $this->source_id,
                'before_balance'    => null,
                'after_balance'     => null
            ];
        }

        //$this->insertPromotionTransferLog($params, "nccu-adjustment");
        return $response;
    }

    /**
    * Record ARP-S Transaction
    *
    * @param mixed accountNumber
    * @param integer transaction_id
    * @param string transactionStatus
    * @param string promotion_type
    * @param string transaction_type = Deposit/Withdrawal
    * @param integer amount
    * @param integer promo_before_balance
    * @param integer promo_after_balance
    * @param integer provider_before_balance
    * @param integer provider_after_balance
    * @param integer asset_id
    *
    * @return array message = Record successfully
    */
    public function recordTransaction()
    {
        //Validate required params = ['transaction_id', 'transaction_status', 'account_number', 'amount', 'promotion_type', 'transaction_type', 'promo_before_balance', 'promo_after_balance', 'asset_id'];
        $this->validateScenario('arps-record');

        try{
            $response = [
                'message'     => "Record successfully.",
            ];

            $params = [
                'account_number'            => $this->accountNumber,
                'transaction_id'            => strval($this->transaction_id),
                'status'                    => $this->transaction_status,
                'promotion_type'            => $this->promotionType,
                'transaction_type'          => intval($this->transaction_type),
                'amount'                    => $this->amount,
                'source_type'               => "Wallet",
                'source_id'                 => 1,
                'ref_source_type'           => $this->ref_source_type,
                'ref_source_id'             => $this->ref_source_id,
                'before_balance'            => $this->promo_before_balance,
                'after_balance'             => $this->promo_after_balance,
                'ref_before_balance'        => $this->provider_before_balance,
                'ref_after_balance'         => $this->provider_after_balance,
                'asset_id'                  => $this->asset_id,
            ];

        }catch(\Exception $e){
            $response = [
                'message'     => "Record unsuccessfully.",
            ];

            $params = [
                'account_number'            => $this->accountNumber ?? null,
                'transaction_id'            => strval($this->transaction_id) ?? null,
                'status'                    => "SiG Error",
                'promotion_type'            => $this->promotionType ?? null,
                'transaction_type'          => intval($this->transaction_type) ?? null,
                'amount'                    => $this->amount ?? null,
                'source_type'               => $this->source_type ?? null,
                'source_id'                 => $this->source_id ?? null,
                'ref_source_type'           => $this->ref_source_type ?? null,
                'ref_source_id'             => $this->ref_source_id ?? null,
                'before_balance'            => $this->promo_before_balance ?? null,
                'after_balance'             => $this->promo_after_balance ?? null,
                'ref_before_balance'        => $this->provider_before_balance ?? null,
                'ref_after_balance'         => $this->provider_after_balance ?? null,
                'asset_id'                  => $this->asset_id ?? null,
            ];
        }

        $this->insertPromotionTransferLog($params, "arps");
        return $response;
    }

    //Check Promotion Type
    public function checkBalancetype()
    {
        //Validate required param = promotion_type
        $this->validateScenario('promotion_type');
    }

    //Insert Promotion Transfer Log
    /**
    * Insert Promotion Transfer Log
    *
    * @param array $params
    * @param string $freeplay_type
    *
    * @return void
    */
    public function insertPromotionTransferLog($params, $freeplay_type){
        $type = $this->getPromotionTypeId($params['promotion_type']); // Set to correct type id
        $params['promotion_type'] = $type['id'];
        //Insert Promtion Transfer log
        $model = new PromotionTransfer();

        //Get userid and accountid
        $account = $model->getAccountByAccountNumber($params['account_number']);

        //Deposit and Withdrawal adjustment
        if($freeplay_type == "peso-adjustment" || $freeplay_type == "nccu-adjustment"){
            $model->user_id             = $account->userid ?? null;
            $model->account_id          = $account->accountid ?? null;
            $model->account_number      = $params['account_number'];
            $model->promotion_type      = $params['promotion_type'];
            $model->transaction_type    = $params['transaction_type'];
            $model->amount              = $params['amount'];
            $model->source_type         = 'Wallet';
            $model->source_id           = 4; // 1 - ACSC, 4 - SiG
            $model->ref_source_type     = $params['ref_source_type'];
            $model->ref_source_id       = $params['ref_source_id'];
            $model->status              = $params['status'];
            $model->before_balance      = $params['before_balance'];
            $model->after_balance       = $params['after_balance'];
            $model->ref_before_balance  = $params['ref_before_balance'];
            $model->ref_after_balance   = $params['ref_after_balance'];
            $model->asset_id            = $params['asset_id'] ?? null;
            $model->created_at          = date("Y-m-d H:i:s");

        } else { //ARPS
            $model->user_id             = $account->userid ?? null;
            $model->account_id          = $account->accountid ?? null;
            $model->account_number      = $params['account_number'];
            $model->transaction_id      = $params['transaction_id'] ?? null;
            $model->promotion_type      = $params['promotion_type'];
            $model->transaction_type    = $params['transaction_type'];
            $model->amount              = $params['amount'];
            $model->source_type         = 'Wallet';
            $model->source_id           = 1;
            $model->ref_source_type     = $params['ref_source_type'];
            $model->ref_source_id       = $params['ref_source_id'];
            $model->status              = $params['status'];
            $model->before_balance      = $params['before_balance'];
            $model->after_balance       = $params['after_balance'];
            $model->ref_before_balance  = $params['ref_before_balance'] ?? null;
            $model->ref_after_balance   = $params['ref_after_balance'] ?? null;
            $model->asset_id            = $params['asset_id'];
            $model->created_at          = date("Y-m-d H:i:s");
        }

        Log::debug("Insert Promotion Transfer Log ,".var_export($params, true),$this->log_category);
        if(!$model->save()){
            if (strpos(current($model->getFirstErrors()), "already been taken") !== false) {
                Log::error("Duplicated transaction_id " . $params['transaction_id'] . " for " . strtoupper($freeplay_type) . ".");
                throw new \Exception("Duplicated transaction_id " . $params['transaction_id'] . " for " . strtoupper($freeplay_type) . ".", 511);
            }else{
                Log::error(current($model->getFirstErrors()));
                throw new \Exception(current($model->getFirstErrors()), 511);
            }
        }
    }

    // Get balance/promotion type Id
    // TODO: change to get from promotion_type table's id instead by searching from promotion_type.shortname or something
    protected function getPromotionTypeId($promotion_type)
    {
        $type = new PromotionType();
        $id = $type->getPromotionType($promotion_type);

        if($id == false || empty($id)){
            return $id = null;
        }else{
            return $id;
        }
    }

    //Return Promotion type, 1 = BP, 2 = SP, 3 = NCCU, ? = Unknown
    protected function getPromotionTypeById($promotion_id)
    {
        $promotion_type = new PromotionType();
        $type = $promotion_type->getPromotionTypeById($promotion_id);

        if($type == false || empty($type)){
            return $type = "Unknown";
        }else{
            return $type;
        }
    }

    //Return Transaction type, 0 = Deposit, 1 = Withdraw, ? = Unknown
    protected function getTransactionType($transaction_type)
    {
        $type = "Unknown";
        switch (strval($transaction_type)) {
            case '0':
                $type = "Deposit";
                break;
            case '1':
                $type = "Withdraw";
                break;
            default:
                $type = "Unknown";
                break;
        }

        return $type;
    }

    /**
    * Allow ARP to check record in SiG
    *
    * @param mixed $account_number
    * @param integer $transaction_id
    *
    * @return array accountNumber           - Patron account number
    * @return array transactionTime         - The trasaction date time
    * @return array transactionStatus       - The status of the transaction
    * @return array transactionAmount       - The amount for the transaction
    * @return array promo_before_balance    - Before promo balance from promotion_transfer table
    * @return array promo_after_balance     - After promo balance from promotion_transfer table
    * @return array provider_before_balance - Before provider balance from promotion_transfer table
    * @return array provider_after_balance  - After provider balance from promotion_transfer table
    * @return array asset_id                - Asset ID from promotion-transfer table
    */
    public function sigCheckTransaction()
    {
        $this->validateScenario('sig_check_transaction');

        try {
            $transaction = PromotionTransfer::find()->where([
                'account_number'  => $this->accountNumber,
                'transaction_id'  => $this->transaction_id,
                'ref_source_type' => $this->ref_source_type,
                'ref_source_id'   => $this->ref_source_id,
                ])->one();
        } catch (\Exception $e) {
            Log::error('SiG Check Transaction Failed, Account Number: '.$this->accountNumber.', '.$e);
            throw new \Exception('Server error', 500);
        }

        if (!empty($transaction))
        {
            return [
                "account_number"          => $transaction->account_number ?? null,
                "transaction_id"          => $transaction->transaction_id ?? null,
                "transaction_status"      => $transaction->status ?? null,
                "transaction_type"        => $this->getTransactionType($transaction->transaction_type),
                "amount"                  => $transaction->amount ?? null,
                "promotion_type"          => $this->getPromotionTypeById($transaction->promotion_type),
                "promo_before_balance"    => $transaction->before_balance ?? null,
                "promo_after_balance"     => $transaction->after_balance ?? null,
                "provider_before_balance" => $transaction->ref_before_balance ?? null,
                "provider_after_balance"  => $transaction->ref_after_balance ?? null,
                "asset_id"                => $transaction->asset_id ?? null,
            ];
        }
        else
        {
            throw new \Exception('Cannot find transaction', 505);
        }
    }

    /**
    * Withdraw promo credits from ACSC to SiG bucket
    *
    * @param mixed $account_number
    * @param integer $ref_source_id
    * @param string $ref_source_type = Provider/Wallet
    *
    * @return array bp - The amount of Bonus Peso deducted from ACSC
    * @return array sp - The amount of Solaire Peso deducted from ACSC
    */
    public function bucketDeposit()
    {
        $this->validateScenario('bucket_deposit');

        //1. Call balance api
        //Check peso balance
        $peso_balance = $this->pesoInquiry();
        $bp_response = 0;
        $sp_response = 0;
        //$nccu_response = 0;
        Log::debug("Account Number :".$this->accountNumber." Getting ACSC Promo Credit");

        if(isset($peso_balance['solairePesoBonusPesoInquiry']) && $peso_balance != null)
        {
            $bonus_peso   = $peso_balance['solairePesoBonusPesoInquiry']['currentBonusPeso'];
            $solaire_peso = $peso_balance['solairePesoBonusPesoInquiry']['currentSolairePeso'];

            // Convert all balance to cent
            $bonus_peso   = str_replace([','], '', $peso_balance['solairePesoBonusPesoInquiry']['currentBonusPeso']) * 100;
            $solaire_peso = str_replace([','], '', $peso_balance['solairePesoBonusPesoInquiry']['currentSolairePeso']) * 100;
            Log::debug("Account Number :".$this->accountNumber.", ACSC BP: ".$bonus_peso.", SP: ".$solaire_peso);
        }else
        {
            Log::error("BP/SP API failed. Account Number :".$this->accountNumber);
            throw new \Exception("BP/SP API failed.", 500);
        }

        //Check nccu balance
        // $nccu_balance = $this->nccuInquiry();

        // if($nccu_balance['GetNonCashableCreditsResponse']['ResponseCode'] === "000" || $nccu_balance['GetNonCashableCreditsResponse']['ResponseCode'] !== "" && $nccu_balance != null){
        //     $nccu_peso    = $nccu_balance['GetNonCashableCreditsResponse']['TotalNonCashableCredits'];
        //     $nccu_peso    = str_replace([','], '', $nccu_peso); // NCCU is already in cent
        // }else{
        //     throw new \Exception("NCCU API failed.", 500);
        // }

        //2. If promo credits exist insert multiple records for different promo credits
        if($bonus_peso >= 1)
        {
            Log::debug("Account Number :".$this->accountNumber.", Deposit ACSC BP to SiG, Amount: ".$bonus_peso);
            $bp_response = $this->sigDepositProcess($this->accountNumber, $bonus_peso, $bonus_peso, "BP", "Deposit", $this->ref_source_id, $this->ref_source_type, $this->provider);
        }

        if($solaire_peso >= 1)
        {
            Log::debug("Account Number :".$this->accountNumber.", Deposit ACSC SP to SiG, Amount: ".$solaire_peso);
            $sp_response = $this->sigDepositProcess($this->accountNumber, $solaire_peso, $solaire_peso, "SP", "Deposit", $this->ref_source_id, $this->ref_source_type, $this->provider);
        }

        // if($nccu_peso >= 1)
        // {
        //     $nccu_response = $this->sigDepositProcess($this->account_number, $nccu_peso, "NCCU", "Withdrawal", $this->ref_source_id);
        // }

        if($bp_response == false || $bp_response == null)
        {
            Log::error("Account Number :".$this->accountNumber.", Deposit ACSC BP to SiG Failed, Response: ".$bp_response);
            return $bp_response;
        }

        if($sp_response == false || $sp_response == null)
        {
            Log::error("Account Number :".$this->accountNumber.", Deposit ACSC SP to SiG Failed, Response: ".$sp_response);
            return $sp_response;
        }

        // if($nccu_response == false || $nccu_response == null)
        // {
        //     return $nccu_response;
        // }

        //return converted dollar amount
        $response = [
            'bp'    => strval($bp_response) ?? 0,
            'sp'    => strval($sp_response) ?? 0,
            //'nccu'  => strval($nccu_response) ?? 0,
        ];

        return $response;
    }

    /**
    * Deposit promo credit to ACSC bucket (Deposit all the amount in SiG bucket back to ACSC bucket)
    *
    * @param mixed $account_number
    * @param string $promotion_type
    * @param integer $ref_source_id
    * @param string $ref_source_type = Provider/Wallet
    *
    * @return array message - A message of "OK" to indicate successful withdrawal
    */
    public function bucketWithdrawal()
    {
        $this->validateScenario('bucket_withdrawal');

        //Check peso balance
        $peso_balance = $this->pesoInquiry();
        Log::debug("Account Number :".$this->accountNumber." Getting ACSC Promo Credit");

        if(isset($peso_balance['solairePesoBonusPesoInquiry']) && $peso_balance != null)
        {
            $bonus_peso   = $peso_balance['solairePesoBonusPesoInquiry']['currentBonusPeso'];
            $solaire_peso = $peso_balance['solairePesoBonusPesoInquiry']['currentSolairePeso'];

            // Convert all balance to cent
            $bonus_peso   = str_replace([','], '', $peso_balance['solairePesoBonusPesoInquiry']['currentBonusPeso']) * 100;
            $solaire_peso = str_replace([','], '', $peso_balance['solairePesoBonusPesoInquiry']['currentSolairePeso']) * 100;
            Log::debug("Account Number :".$this->accountNumber.", ACSC BP: ".$bonus_peso.", SP: ".$solaire_peso);
        }else
        {
            Log::error("BP/SP API failed. Account Number :".$this->accountNumber);
            throw new \Exception("BP/SP API failed.", 500);
        }

        $account = new PromotionAccount();
        Log::debug("Account Number :".$this->accountNumber." Deposit Promo Credit to ACSC");

        //Find existing record for Promotion Account
        $type = $this->getPromotionTypeId($this->promotionType); // Set to correct type id
        $promotion_type = $type['id'];

        $promotion_account = $account->getExistingRecord($this->accountNumber, $promotion_type);
        $before_balance = ($promotion_type == 1) ? $bonus_peso : $solaire_peso;

        if($promotion_account['sig_balance'] >= 1)
        {
            Log::debug("Account Number :".$this->accountNumber.", Deposit Promo Credit to ACSC, Amount: ".$promotion_account['sig_balance']);
            $this->sigWithdrawalProcess($this->accountNumber, $before_balance, $promotion_account['sig_balance'], $this->promotionType, "Withdrawal", $this->ref_source_id, $this->ref_source_type, $this->provider);

            return array(
                'message' => "OK"
            );
            //return true;
        }else{
            Log::error("Account Number : ".$this->accountNumber." Failed to deposit back to ACSC wallet.");
            throw new \Exception("Failed to deposit back to ACSC wallet. Account Number".$this->accountNumber, 501);
        }
    }

    /**
     * This function is used to include the process to withdraw the Promo credits from SiG to ACSC
     *
     * @param mixed $account_number
     * @param int $before_balance
     * @param int $amount
     * @param string $promotion_type
     * @param string $type - Transaction type
     * @param int $ref_source_id
     * @param string $ref_source_type
     * @param string $provider
     *
     * @return int $amount
     */
    protected function sigWithdrawalProcess($account_number, $before_balance, $amount, $promotion_type, $type, $ref_source_id, $ref_source_type = "Provider", $provider)
    {
        //Init records
        $temp_record_fields = array(
            "account_number"        => $account_number,
            "promotion_type"        => $promotion_type, // Promotion type, 1 = BP, 2 = SP, 3 = NCCU
            "transaction_id"        => "", // null for the moment
            "transaction_type"      => 1, // 0 = Deposit, 1 = Withdraw
            "amount"                => (int)$amount,
            "status"                => "Incomplete", // 1 - Incomplete, 2 - Completed, -1 - Failed
            "ref_source_id"         => $ref_source_id, // 1 - ACSC, 4 - SiG
            "ref_source_type"       => $ref_source_type,
            "ref_before_balance"    => $before_balance,
            "ref_after_balance"     => $before_balance,
            "source_id"             => 4, // 11 - OPMG
            "source_type"           => "Wallet",
            "currency"              => 1,
            "clientip"              => $this->clientip,
        );

        $promotion_transfer_form = new PromotionTransferForm();

        $transaction_id = (int)$promotion_transfer_form->insertPromotionTransfer($temp_record_fields);

        $update_transaction = $promotion_transfer_form->updateTansactionID($transaction_id);

        $promotion_account_form = new PromotionAccountForm();
        $promotion_account = $promotion_account_form->getExistingRecord($account_number, $promotion_type);

        if($promotion_account == null || $promotion_account == false){
            $sig_balance = $amount;
        }else{
            $sig_balance = $promotion_account['sig_balance'] ?? 0;
        }

        $promotion_account_fields = array(
            "account_number"        => $account_number,
            "promotion_type"        => $promotion_type, // Promotion type, 1 = BP, 2 = SP, 3 = NCCU
            "sig_balance"           => $amount * -1,
        );
        if($promotion_account_form->getExistingRecord($account_number, $promotion_type) != null){
            $promotion_account_form->updatePromotionAccount($promotion_account_fields);
        }else{
            throw new \Exception("No promotion account record found.", 550);
        }

        //Increase ACSC promo credits, by calling Solaire API
        if($promotion_type === "BP" || $promotion_type === "SP")
        {
            $pesoAdjustment = $this->pesoAdjustment($type, $promotion_type, intval($amount), $provider);

            if(isset($pesoAdjustment['transactionStatus']) && $pesoAdjustment['transactionStatus'] == "Failed"){

                //Set promotion transfer status to incomplete
                $promotion_transfer_form->updatePromotionTransfer([
                    "status"        => "Failed",
                ], $transaction_id);

                throw new \Exception("Front money(Promo Credit) withdraw failed.", 550);
            }
        }else if($promotion_type == "NCCU"){
            $nccu_adjustment = $this->nccuAdjustment($account_number, $type, $amount);
        }

        $promotion_transfer_form->updatePromotionTransfer([
            "account_number"        => $account_number,
            "before_balance"        => $sig_balance ?? 0,
            "after_balance"         => $sig_balance - $amount ?? 0,
        ], $transaction_id);

        $promotion_transfer_form->updatePromotionTransfer([
            "status"        => "Complete",
        ], $transaction_id);

        $promotion_transfer_form->updatePromotionTransfer([
            "account_number"        => $account_number,
            "ref_after_balance"     => $amount,
        ], $transaction_id);

        //Return promo credit amount
        return $amount/100;
    }

    /**
    * Function to isolate repeated code for bucketdeposit and bucketwithdrawal
    *
    * @param mixed $account_number
    * @param integer $before_balance
    * @param integer $amount
    * @param string $promotion_type
    * @param string $type = Deposit/Withdrawal
    * @param integer $ref_source_id
    * @param string $ref_source_type = Provider/Wallet
    *
    * @return integer amount - The amount that deducted from ACSC and is /100 to get the dollar value
    */
    protected function sigDepositProcess($account_number, $before_balance, $amount, $promotion_type, $type, $ref_source_id, $ref_source_type = "Provider", $provider)
    {
        $temp_record_fields = array(
            "account_number"        => $account_number,
            "promotion_type"        => $promotion_type, // Promotion type, 1 = BP, 2 = SP, 3 = NCCU
            "transaction_id"        => "", // null for the moment
            "transaction_type"      => ($type == 'Deposit') ? 0 : 1, // 0 = Deposit, 1 = Withdraw
            "amount"                => (int)$amount,
            "status"                => "Incomplete", // 1 - Incomplete, 2 - Completed, -1 - Failed
            "ref_source_id"         => $ref_source_id, // 1 - ACSC, 4 - SiG
            "ref_source_type"       => $ref_source_type,
            "ref_before_balance"    => $before_balance,
            "ref_after_balance"     => $before_balance,
            "source_id"             => 4, // 11 - OPMG
            "source_type"           => "Wallet",
            "currency"              => 1,
            "clientip"              => $this->clientip,
        );

        $promotion_transfer_form = new PromotionTransferForm();

        $transaction_id = (int)$promotion_transfer_form->insertPromotionTransfer($temp_record_fields);

        $update_transaction = $promotion_transfer_form->updateTansactionID($transaction_id);

        //3. Deduct or Increase ACSC promo credits, by calling Solaire API
        if($promotion_type === "BP" || $promotion_type === "SP")
        {
            $pesoAdjustment = $this->pesoAdjustment($type, $promotion_type, intval($amount), $provider);

            if(isset($pesoAdjustment['transactionStatus']) && $pesoAdjustment['transactionStatus'] == "Failed"){
                throw new \Exception("Front money(Promo Credit) withdraw failed.", 550);
            }
        }else if($promotion_type == "NCCU"){
            $nccu_adjustment = $this->nccuAdjustment($account_number, $type, $amount);
        }

        $promotion_account_form = new PromotionAccountService();
        $promotion_account = $promotion_account_form->getExistingRecord($account_number, $promotion_type);

        if($promotion_account == null || $promotion_account == false){
            $sig_balance = $amount;
        }else{
            $sig_balance = $promotion_account['sig_balance'] ?? 0;
        }

        //Call different transaction type function to cope for different scenarios
        if($type === "Deposit") //ACSC to SiG bucket
        {
            $promotion_account_fields = array(
                "account_number"        => $account_number,
                "promotion_type"        => $promotion_type, // Promotion type, 1 = BP, 2 = SP, 3 = NCCU
                "sig_balance"           => $amount,
            );
            if($promotion_account_form->getExistingRecord($account_number, $promotion_type) != null){
                $update_promotion_account = $promotion_account_form->updatePromotionAccount($promotion_account_fields);
            }else{
                $promotion_account_fields = array(
                    "account_number"        => $account_number,
                    "promotion_type"        => $promotion_type, // Promotion type, 1 = BP, 2 = SP, 3 = NCCU
                    "sig_balance"           => $amount,
                    "status"                => "Completed",
                );
                $update_promotion_account = $promotion_account_form->updatePromotionAccount($promotion_account_fields);
            }

            $update_transaction = $promotion_transfer_form->updatePromotionTransfer([
                "account_number"        => $account_number,
                "before_balance"        => $sig_balance ?? 0,
                "after_balance"         => $sig_balance + $amount ?? 0,
            ], $transaction_id);

        }else if($type === "Withdrawal") //SiG bucket to ACSC
        {
            $promotion_account_fields = array(
                "account_number"        => $account_number,
                "promotion_type"        => $promotion_type, // Promotion type, 1 = BP, 2 = SP, 3 = NCCU
                "sig_balance"           => $amount * -1,
            );
            if($promotion_account_form->getExistingRecord($account_number, $promotion_type) != null){
                $update_promotion_account = $promotion_account_form->updatePromotionAccount($promotion_account_fields);
            }else{
                $promotion_account_fields = array(
                    "account_number"        => $account_number,
                    "promotion_type"        => $promotion_type, // Promotion type, 1 = BP, 2 = SP, 3 = NCCU
                    "sig_balance"           => $amount * -1,
                    "status"                => "Completed",
                );
                $update_promotion_account = $promotion_account_form->updatePromotionAccount($promotion_account_fields);
            }

            $update_transaction = $promotion_transfer_form->updatePromotionTransfer([
                "account_number"        => $account_number,
                "before_balance"        => $sig_balance ?? 0,
                "after_balance"         => $sig_balance - $amount ?? 0,
            ], $transaction_id);
        }

        $update_transaction = $promotion_transfer_form->updatePromotionTransfer([
            "status"        => "Complete",
        ], $transaction_id);

        //6. Update temp record with complete status, before and after amount
        if($type === "Deposit") //ACSC to SiG bucket
        {
            $update_transaction = $promotion_transfer_form->updatePromotionTransfer([
                "account_number"        => $account_number,
                "after_balance"         => $sig_balance,
                "ref_after_balance"     => $amount * -1,
            ], $transaction_id);

        }else if($type === "Withdrawal") //SiG bucket to ACSC
        {
            $update_transaction = $promotion_transfer_form->updatePromotionTransfer([
                "account_number"        => $account_number,
                "ref_after_balance"     => $amount,
            ], $transaction_id);
        }

        //Return promo credit amount
        return $amount/100;
    }

    /**
    * Game Provider deposit into SiG bucket init function
    *
    * @param mixed $account_number
    * @param integer $amount
    * @param string $promotion_type
    * @param string $provider = arpstudio/opmg/others
    *
    * @return array $transaction_id - The unique transaction id after the initial record is inserted successfully
    */
    public function ProviderDepositInit()
    {
        $this->validateScenario('provider_deposit_init');
        Log::debug("Account Number ".$this->accountNumber." Deposit Init",$this->log_category);

        //Get SiG bucket balance second time to check for the updated sig bucket balance
        $balance = $this->getSiGBucketBalance();
        $sig_bonus_peso = 0;
        $sig_solaire_peso = 0;

        if($this->promotionType == "BP"){
            $sig_bonus_peso     = (int)($balance['bp'] ?? 0);
            Log::debug("Account Number ".$this->accountNumber." BP , ".$sig_bonus_peso,$this->log_category);
        }else{
            $sig_solaire_peso   = (int)($balance['sp'] ?? 0);
            Log::debug("Account Number ".$this->accountNumber." SP , ".$sig_solaire_peso,$this->log_category);
        }

        $platforms = new Platforms();
        $platforms = $platforms->getExistingRecord($this->provider);

        $temp_record_fields = array(
            "account_number"        => $this->accountNumber,
            "promotion_type"        => $this->promotionType, // Promotion type, 1 = BP, 2 = SP, 3 = NCCU
            "transaction_id"        => "", // null for the moment
            "transaction_type"      => 0, // 0 = Deposit, 1 = Withdraw
            "amount"                => (int)$this->amount,
            "status"                => "Incomplete", // 1 - Incomplete, 2 - Completed, -1 - Failed
            "ref_source_id"         => $platforms['platformid'] ?? 0,
            "ref_source_type"       => "Platform",
            "source_id"             => 4, // 4 - SiG (In SiG perspective)
            "source_type"           => "Wallet",
            "before_balance"        => ($this->promotionType == "BP") ? $sig_bonus_peso : $sig_solaire_peso, // Either one is 0
            "after_balance"         => ($this->promotionType == "BP") ? $sig_bonus_peso : $sig_solaire_peso, // Either one is 0
            "currency"              => 1,
            "clientip"              => $this->clientip,
        );

        $promotion_transfer_form = new PromotionTransferForm();

        $transaction_id = (int)$promotion_transfer_form->insertPromotionTransfer($temp_record_fields);

        $update_transaction = $promotion_transfer_form->updateTansactionID($transaction_id);

        if($update_transaction != false){
            Log::debug("Insert Promotion Transfer Log Success ,".var_export($temp_record_fields, true));
            return array(
                'transaction_id' => $transaction_id
            );
        }else{
            Log::error("Update Promotion Transfer Transaction ID ".$transaction_id.' Failed');
        }
    }

    /**
    * Game Provider deposit into SiG bucket process function
    *
    * @param mixed $account_number
    * @param integer $amount
    * @param string $promotion_type
    * @param integer $transaction_id
    *
    * @return array transaction_id - The unique transaction id after record is inserted successfully for different Promotion Type
    * @return array promotion_type - The Promotion Type
    * @return array amount         - The amount of Game Provider wish to deposit back to SiG Bucket
    */
    public function ProviderDepositComplete()
    {
        $this->validateScenario('provider_deposit_complete');

        $promotion_transfer_form = new PromotionTransferForm();

        $params = array(
            "ref_before_balance" => $this->amount,
        );

        $update_promotion_transfer = $promotion_transfer_form->updatePromotionTransfer($params, $this->transaction_id);

        $promotion_account_form = new PromotionAccountForm();

        //Will return sig_balance if sig_balance is updated
        $sig_balance = $promotion_account_form->updatePromotionAccount([
            "promotion_type" => $this->promotionType,
            "account_number" => $this->accountNumber,
            "sig_balance"    => $this->amount
        ], $this->transaction_id);

        $update_promotion_transfer = $promotion_transfer_form->updatePromotionTransfer(["status" => "Complete"], $this->transaction_id);

        $update_promotion_transfer = $promotion_transfer_form->updatePromotionTransfer([
            //"before_balance" => intval($sig_balance['sig_balance']),
            "after_balance" => intval($this->amount),
        ], $this->transaction_id);

        return array(
            'transaction_id' => $this->transaction_id,
            'promotion_type' => $this->promotionType,
            'amount'         => $this->amount
        );
    }

    /**
    * Withdraw from SiG Bucket to Game Provider init function
    *
    * @param mixed $account_number
    * @param integer $amount
    * @param integer $promo_balance
    * @param integer $bp_balance
    * @param integer $sp_balance
    * @param string $provider
    *
    * @return array bp                 - The amount of Bonus Peso deducted from SiG bucket that will get from ACSC if there is insufficient amount in SiG Bucket
    * @return array bp_transaction_id  - The unique transaction id after record is inserted successfully for promotion type Bonus Peso
    * @return array sp                 - The amount of Solaire Peso deducted from SiG bucket that will get from ACSC if there is insufficient amount in SiG Bucket
    * @return array sp_transaction_id  - The unique transaction id after record is inserted successfully for promotion type Solaire Peso
    */
    public function ProviderWithdrawalCheckAmount()
    {
        $this->validateScenario('provider_withdrawal_init');

        //Check amount in SiG bucket and ACSC whether sufficient for amount passing in
        //Get SiG bucket balance
        $balance = $this->getSiGBucketBalance();
        $amount = $this->amount;

        $sig_bonus_peso     = (int)($balance['bp'] ?? 0);
        $sig_solaire_peso   = (int)($balance['sp'] ?? 0);

        $peso_bonus_peso    = 0;
        $peso_solaire_peso  = 0;
        $promo_balance      = 0;
        $total_bonus_peso   = 0;
        $total_solaire_peso = 0;

        //Get ACSC Bucket balance
        $peso_balance = $this->pesoInquiry();

        if(isset($peso_balance['solairePesoBonusPesoInquiry']) && $peso_balance != null)
        {
            $peso_bonus_peso   = $peso_balance['solairePesoBonusPesoInquiry']['currentBonusPeso'];
            $peso_solaire_peso = $peso_balance['solairePesoBonusPesoInquiry']['currentSolairePeso'];

            // Convert all balance to cent
            $peso_bonus_peso   = str_replace([','], '', $peso_balance['solairePesoBonusPesoInquiry']['currentBonusPeso']) * 100;
            $peso_solaire_peso = str_replace([','], '', $peso_balance['solairePesoBonusPesoInquiry']['currentSolairePeso']) * 100;

            $total_bonus_peso = intval($sig_bonus_peso + $peso_bonus_peso);
            $total_solaire_peso = intval($sig_solaire_peso + $peso_solaire_peso);

        }else
        {
            Log::error("BP/SP API failed. Account Number :".$this->accountNumber);
            throw new \Exception("BP/SP API failed.", 500);
        }

        if($amount > ($total_bonus_peso + $total_solaire_peso)){
            Log::error("Please contact administrator as the Promo Credit is not sufficient.");
            throw new \Exception ("Please contact administrator as the Promo Credit is not sufficient.", 524);
        }

        if($amount > ($sig_bonus_peso + $sig_solaire_peso)){
            //Check promo balance
            $promo_balance = $amount - $sig_bonus_peso - $sig_solaire_peso;
            $bp_withdraw_amount = $promo_balance > $peso_bonus_peso ? $peso_bonus_peso : $promo_balance;

            if($peso_bonus_peso >= 1 && $promo_balance >= 1)
            {
                $bp_response = $this->sigDepositProcess($this->accountNumber, $peso_bonus_peso, $bp_withdraw_amount, "BP", "Deposit", 1, "Wallet", $this->provider);
                Log::debug("BP Response: " . var_export($bp_response, true));
            }

            $sp_withdraw_amount = $promo_balance - $bp_withdraw_amount;

            if($peso_solaire_peso >= 1 && $sp_withdraw_amount >= 1)
            {
                $sp_response = $this->sigDepositProcess($this->accountNumber, $peso_solaire_peso, $sp_withdraw_amount, "SP", "Deposit", 1, "Wallet", $this->provider);
                Log::debug("SP Response: " . var_export($sp_response, true));
            }
        }

        $balance = $this->getSiGBucketBalance();

        $toWithdrawAmount = $this->amount;
        $sig_bonus_peso     = (int)($balance['bp'] ?? 0);
        $sig_solaire_peso   = (int)($balance['sp'] ?? 0);
        $first_transaction = false;
        $promotion_transfer_form = new PromotionTransferForm();

        $bp_amount = ($toWithdrawAmount >= $sig_bonus_peso) ? $sig_bonus_peso : $toWithdrawAmount;
        if ($bp_amount > 0){
            $first_transaction = $this->ProviderWithdrawalInit($this->accountNumber, $bp_amount, $this->bp_balance, "BP", $this->provider, $this->clientip);

            if($first_transaction == false || $first_transaction == null){
                Log::error("Failed to insert Promotion Transaction record. Error: $first_transaction");
                throw new \Exception ("Failed to insert Promotion Transaction record. Error: $first_transaction", 525);
            }
            $first_sig_transaction = $promotion_transfer_form->getExistingRecord($first_transaction['transaction_id']);

        }

        $toWithdrawAmount -= $bp_amount;
        $sp_amount = ($toWithdrawAmount >= $sig_solaire_peso) ? $sig_solaire_peso: $toWithdrawAmount;

        if ($sp_amount > 0){
            $second_transaction = $this->ProviderWithdrawalInit($this->accountNumber, $sp_amount, $this->sp_balance, "SP", $this->provider, $this->clientip);

            if($second_transaction == false || $second_transaction == null){
                Log::error("Failed to insert Promotion Transaction record. Error: $first_transaction");
                throw new \Exception ("Failed to insert Promotion Transaction record. Error: $first_transaction", 525);
            }
            $second_sig_transaction = $promotion_transfer_form->getExistingRecord($second_transaction['transaction_id']);

        }



        $first_sig_transaction_id = $first_sig_transaction['transaction_id'] ?? null;
        $second_sig_transaction_id = $second_sig_transaction['transaction_id'] ?? null;

        return array(
            'bp'                    => $bp_amount,
            'bp_transaction_id'     => $first_transaction['transaction_id'] ?? null,
            'bp_sig_transaction_id' => $first_sig_transaction_id,
            'sp'                    => $sp_amount,
            'sp_transaction_id'     => $second_transaction['transaction_id'] ?? null,
            'sp_sig_transaction_id' => $second_sig_transaction_id,
        );
    }

    /**
    * Withdraw from SiG bucket to Game Provider init function
    *
    * @param mixed $account_number
    * @param integer $amount
    * @param integer $promo_balance
    * @param string $promotion_type
    * @param string $provider
    *
    * @return array $transaction_id - The unique transaction id after the initial record is inserted successfully
    */
    public function ProviderWithdrawalInit($account_number, $amount, $promo_balance, $promotion_type = "BP",  $provider = "others", $clientip){
        $balance = $this->getSiGBucketBalance();

        $sig_bonus_peso     = (int)($balance['bp'] ?? 0);
        $sig_solaire_peso   = (int)($balance['sp'] ?? 0);
        Log::debug("Withdraw Init. Account Number ".$this->accountNumber."SiG BP: " . $sig_bonus_peso . ", SP ,".$sig_solaire_peso,$this->log_category);

        $platforms = new Platforms();
        $platforms = $platforms->getExistingRecord($provider);

        //Create init "Incomplete" record
        $temp_record_fields = array(
            "account_number"        => $account_number,
            "promotion_type"        => $promotion_type, // Promotion type, 1 = BP, 2 = SP, 3 = NCCU
            "transaction_id"        => "", // null for the moment
            "transaction_type"      => 1, // 0 = Deposit, 1 = Withdraw
            "amount"                => (int)$amount,
            "status"                => "Incomplete", // 1 - Incomplete, 2 - Completed, -1 - Failed
            "ref_source_id"         => $platforms['platformid'] ?? 0,
            "ref_source_type"       => "Platform",
            "source_id"             => 4, // 4 - SiG (In SiG perspective)
            "source_type"           => "Wallet",
            "before_balance"        => ($promotion_type == "BP") ? $sig_bonus_peso : $sig_solaire_peso, // Either one is 0 // todo - sig balance
            "after_balance"         => ($promotion_type == "BP") ? $sig_bonus_peso : $sig_solaire_peso, // Either one is 0
            "ref_before_balance"    => $promo_balance,
            "currency"              => 1,
            "clientip"              => $clientip,
        );

        $promotion_transfer_form = new PromotionTransferForm();

        $transaction_id = (int)$promotion_transfer_form->insertPromotionTransfer($temp_record_fields);

        $update_promotion_transfer = $promotion_transfer_form->updateTansactionID($transaction_id);

        $promotion_account_form = new PromotionAccountForm();

        //Will return sig_balance if sig_balance is updated
        $sig_balance = $promotion_account_form->updatePromotionAccount([
            'account_number' => $account_number,
            'promotion_type' => $promotion_type,
            "sig_balance" => ($amount * -1)
        ]);

        $update_promotion_transfer = $promotion_transfer_form->updatePromotionTransfer([
            'account_number' => $this->accountNumber,
            'after_balance' => intval($amount * -1),
        ],
        $transaction_id);

        return array(
            "transaction_id" => $transaction_id,
        );
    }

    /**
    * Withdraw from SiG bucket to Game Provider complete function
    * @param integer $amount
    * @param integer $transaction_id
    *
    * @return boolean true
    */
    public function ProviderWithdrawalComplete()
    {
        $this->validateScenario('provider_withdrawal_complete');

        $promotion_transfer_form = new PromotionTransferForm();

        $promotion_transfer_form = new PromotionTransferForm();
        $promotion_transfer = $promotion_transfer_form->getExistingRecord($this->transaction_id);

        $update_promotion_transfer = $promotion_transfer_form->updatePromotionTransfer([
            "transaction_id"        => $this->transaction_id,
            "status"                => "Complete",
            //"ref_before_balance"    => $this->amount,
            "ref_after_balance"     => $promotion_transfer['before_balance'],
        ], $this->transaction_id);

        return $update_promotion_transfer;
    }

    /**
    * get SiG bucket balance
    * @param mixed $account_number
    *
    * @return array bp - The Bonus Peso amount from SiG bucket
    * @return array sp - The Solaire Peso amount from SiG bucket
    */
    public function getSiGBucketBalance()
    {
        $account = new PromotionAccount();
        $bonus_peso = $account->getExistingRecord($this->accountNumber, 1); // To get Bonus Peso
        $solaire_peso = $account->getExistingRecord($this->accountNumber, 2); // To get Solaire Peso

        return $response = [
            'bp' => (isset($bonus_peso)) ? ($bonus_peso->sig_balance) : 0,
            'sp' => (isset($solaire_peso)) ? ($solaire_peso->sig_balance) : 0
        ];
    }

    //Validate Scenarios
    protected function validateScenario($scenario)
    {
        $this->scenario = $scenario;
        if (!$this->validate()) {
            throw new \Exception(current($this->getFirstErrors()), 2000);
        }
    }
}
