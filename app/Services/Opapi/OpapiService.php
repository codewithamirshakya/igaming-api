<?php 

namespace App\Services\Opapi;

class OpapiService 
{
	protected $baseurl;
	protected $hostid;
	protected $lobbyurl;
	protected $currency;

	public function __construct()
	{
		$opapiconf		= config('params.endpoint.opapi');
		$this->baseurl	= $opapiconf['base_url'];
	}

	/**
	 * Make request
	 * 
	 * @param string $baseUrl
	 * @param string $action
	 * @param array $body
	 */
	private function makeRequest(string $baseUrl, string $action, array $body)
	{
		$url = $baseUrl.$action.'?'.http_build_query($body);

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL            => $url,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT        => 25,
			CURLOPT_RETURNTRANSFER => true
		));
		
		$response = curl_exec($curl);
		
		if ($errorNumber = curl_errno($curl)) {
			// For CURLOPT_TIMEOUT
			if (in_array($errorNumber, array(CURLE_OPERATION_TIMEDOUT, CURLE_OPERATION_TIMEOUTED))) {
				$response = '{"status":false,"error":"API timed out."}';
			 }
			 if (in_array($errorNumber, array(CURLE_COULDNT_RESOLVE_PROXY, CURLE_COULDNT_RESOLVE_HOST))) {
				$response = '{"status":false,"error":"Endpoint failed to resolve."}';
			 }
			 // For CURLOPT_CONNECTTIMEOUT
			 if (in_array($errorNumber, array(CURLE_COULDNT_CONNECT))) {
				$response = '{"status":false,"retry":true,"error":"Endpoint failed to connect."}';
			 }
		}
		
		if (!$response) {
			$response = '{"status":false,"error":"Empty API response"}';
		}

		curl_close($curl);

		return $response;
	}
	
	/**
	 * Op api call
	 * 
	 * @param string $call
	 * @param array $fields
	 * @param int $timeout
	 */
	public function opApi(string $call, array $fields, int $timeout = 25)
	{
		return $this->makeRequest($this->baseurl, $call, $fields);
	}
}