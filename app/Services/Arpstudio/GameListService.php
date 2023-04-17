<?php 

namespace App\Services\Arpstudio;

use App\Models\Game;
use Illuminate\Support\Facades\Http;

class GameListService extends ArpstudioService
{
	public function gameList()
	{
		$params = [
			'notifyid'	=> uniqid()
		];

		$url 		= $this->baseurl. 'client/game/lobby';
		$response 	= $this->makeRequest($url, $params)->toArray();

		// if failed get from db
		if ($response == null || $response['array'] == null || $response == false || $response['desc'] != "succeed") {
			// get it from db
			return Game::where('platformid', 14)->get(); // need to change in repo pattern
		}

		//Map game code into the response
		foreach ($response['array'] as $key => $value) {
			$gameList 	= Game::where('platformid', 14)->where('foreigngame', $response['array'][$key]['gameid'])->first()->toArray();

			$response = $this->prepareResponse($key, $response, $gameList);
		}

		return $response;

	}

	/**
	 * Prepare response
	 * 
	 * @param $key
	 * @param $response
	 * 
	 */
	public function prepareResponse($key, $response, $gameList)
	{
		$response['array'][$key]['gamecode']   = $this->gameCodeMapper[$response['array'][$key]['gameid']];

		$response['array'][$key]['dealericon'] = isset($response['array'][$key]['dealericon']) && !empty($response['array'][$key]['dealericon']) ? $this->dealerIcon . $response['array'][$key]['dealericon'] : null;

		$response['array'][$key]['id']         = isset($response['array'][$key]['quotas'][$key]['id']) ? $response['array'][$key]['quotas'][$key]['id'] : null;
		$response['array'][$key]['identity']   = isset($response['array'][$key]['quotas'][$key]['id']) ? $response['array'][$key]['quotas'][$key]['identity'] : null;
		$response['array'][$key]['type']       = isset($response['array'][$key]['quotas'][$key]['id']) ? $response['array'][$key]['quotas'][$key]['type'] : null;
		$response['array'][$key]['up']         = isset($response['array'][$key]['quotas'][$key]['id']) ? $response['array'][$key]['quotas'][$key]['up'] : null;
		$response['array'][$key]['down']       = isset($response['array'][$key]['quotas'][$key]['id']) ? $response['array'][$key]['quotas'][$key]['down'] : null;

		$response['array'][$key]['ispublic']   = $gameList['ispublic'] ?? null;

		unset($response['array'][$key]['extend']);
		unset($response['array'][$key]['quotas']);

		return $response;
	}
}