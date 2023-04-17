<?php

namespace App\Services\Arpstudio;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArpstudioService
{
	public $baseurl;
	public $appid;
	public $appkey;
	public $startpushcredit;
	public $gameCodeMapper;
	public $dealerIcon;


	public function __construct()
	{
		$arpstdconf				= config('params.endpoint.arpstudio');
		$this->baseurl			= $arpstdconf['base_url'];
		$this->appid			= $arpstdconf['app_id'];
		$this->appkey			= $arpstdconf['app_key'];
		$this->startpushcredit  = config('params.pushcredit.arpstudio.start');
		$this->gameCodeMapper 	= config('params.endpoint.arpstudio.game_code_mapper');
		$this->dealerIcon 		= config('params.endpoint.arpstudio.dealer_icon');
	}

	public function makeRequest($url, $params, $type = "POST")
	{
		return $type == "POST" ? Http::post($url, $params)->json() : Http::get($url, $params)->json();
	}

	public function entering(array $params)
	{

	}

	public function exiting(array $params)
	{

	}

	public function promoIn(array $params)
	{

	}

	/**
	 * Get balance
	 * @param $patron : username
	 */
	public function getBalance($patron)
	{
		$params = [
			'username' => $patron
		];

		return $this->makeRequest($this->baseurl."user/balance", $params, "GET");
	}

	public function deposit($patron, $amount, $transferid, $atype = 1 )
	{
		$amount 		= number_format(abs($amount)/100, 2, '.', ''); // cents to dollar !!!
		$params 		= [ 'username' => $patron, 'amount' => $amount, 'tradeno' => $transferid, 'atype' => $atype ];
		return $this->makeRequest($this->baseurl."user/dw", $params, "POST");
	}

	/**
	 * Deposit specified or all promo credit amount to provider for the given username
	 *
	 * @param mixed $username
	 * @param integer $amount
	 * @return array amount    - The amount from $amount param
	 * @return array bp_amount - Bonus Peso amount that's transferred to game provider
	 * @return array sp_amount - Solaire Peso amount that's transferred to game provider
	 * @return array studioBp - Current Bonus Peso amount in the game provider
	 * @return array studioSp - Current Solaire Peso amount in the game provider
	 */
	public function platformAddPromoCredit($username, $amount = -1)
	{
		// Get Studio Balance
		$studioPromoBalance = $this->getBalance($username)->toArray();

		// TODO : Proper error handling, currently just to get data and don't stop the flow
		if (!$studioPromoBalance || substr( $studioPromoBalance, 0, 1 ) != '{') {
			$studioPromoBalance = ['wallet' => ['balance' => 0, 'atype' => 1], ['balance' => 0, 'atype' => 5], ['balance' => 0, 'atype' => 6]];
		} else {
			$studioPromoBalance = json_decode($studioPromoBalance, true);
		}


		// // Extract Studio Balance
		$bpIndex 	= array_search(5, array_column($studioPromoBalance['wallet'] , 'atype'));
		$studioBp 	= $studioPromoBalance['wallet'][$bpIndex]['balance']*100; // convert to cents
		$spIndex 	= array_search(6, array_column($studioPromoBalance['wallet'] , 'atype'));
		$studioSp 	= $studioPromoBalance['wallet'][$spIndex]['balance']*100; // convert to cents

		$freeplay 					= new FreeplayService();
		$freeplay->accountNumber 	= $username;
		$freeplay->provider 		= "ARPStudio";
		$freeplay->bp_balance 		= $studioBp ?? 0;
		$freeplay->sp_balance 		= $studioSp ?? 0;

		// Get ACSC BP/SP Balance, required when amount is not passed in
		if ($amount == -1) {
			$acscBpSp = $freeplay->pesoInquiry();
			$acscBp = $acscBpSp['solairePesoBonusPesoInquiry']['currentBonusPeso'] ?? "0";
			$acscSp = $acscBpSp['solairePesoBonusPesoInquiry']['currentSolairePeso'] ?? "0";

			// Convert all balance to cent
			$acscBp = intval(str_replace([','], '', $acscBp) * 100);
			$acscSp = intval(str_replace([','], '', $acscSp) * 100);

			// Get Sig Bucket Balance
			$sigBucket = $freeplay->getSiGBucketBalance();

			$freeplay->amount = intval($acscBp + $acscSp + $sigBucket['bp'] + $sigBucket['sp']); // Deduct Full Promo Credit from SiG Bucket and ACSC
		} else {
			$freeplay->amount = $amount;
		}

		if ($freeplay->amount > 0) {
			$providerDeposit = $freeplay->ProviderWithdrawalCheckAmount();
			Log::debug("Account Number ".$username." ACSC PC Withdrawal amount, BP: ".$providerDeposit['bp'].", SP: ".$providerDeposit['sp']);

			// Add the deducted BP and SP to ARP Studio
			if ($providerDeposit['bp'] > 0) {
				Log::debug("Account Number ".$username." Deposit BP to ARPStudio, Transaction ID: ".$providerDeposit['bp_transaction_id']." Amount: ".$providerDeposit['bp']);
				$bpDeposit = json_decode($this->deposit($username, $providerDeposit['bp'], $providerDeposit['bp_transaction_id'], 5), true);

				// Set the promotion_transfer record to Complete and update after_balance
				$freeplay->transaction_id = $providerDeposit['bp_transaction_id'];
				$freeplay->ProviderWithdrawalComplete();
				Log::debug("Account Number ".$username." Deposit BP to ARPStudio Success");
			}

			if ($providerDeposit['sp'] > 0) {
				Log::debug("Account Number ".$username." Deposit SP to ARPStudio, Transaction ID: ".$providerDeposit['sp_transaction_id']." Amount: ".$providerDeposit['sp']);
				$spDeposit = json_decode($this->deposit($username, $providerDeposit['sp'], $providerDeposit['sp_transaction_id'], 6), true);

				// Set the promotion_transfer record to Complete and update after_balance
				$freeplay->transaction_id = $providerDeposit['sp_transaction_id'];
				$freeplay->ProviderWithdrawalComplete();
				Log::debug("Account Number ".$username." Deposit SP to ARP Studio Success");
			}
		}

		// Return transferred BP and SP amount and also current amount in studio
		return [
			'amount' => $freeplay->amount ?? 0,
			'bp_amount' => $providerDeposit['bp'] ?? 0,
			'sp_amount' => $providerDeposit['sp'] ?? 0,
			'studioBp' => $bpDeposit['balance'] ?? 0 * 100,
			'studioSp' => $spDeposit['balance'] ?? 0 * 100,
		];
	}
}
