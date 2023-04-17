<?php

namespace App\Services\Arpstudio;

class RoadTrendService extends ArpstudioService
{
    /**
     * Get road trend
     *
     * @param $params
     * @return array
     */
	public function roadTrend(array $params)
	{
		$parameters = [
			'notifyid' => uniqid(),
			'gameid'   => $params['gameid'] ?? null
		];

		if (!empty($params['gameid'])) {
			return $this->getRoadSheetByGameId($parameters);
		} else {
			return $this->getAllRoadSheet($parameters);
		}

	}

    /**
     * Get Road Sheet by GameId
     *
     * @param $params
     * @return array
     */
	public function getRoadSheetByGameId($params)
	{
		$response = $this->makeRequest($this->baseurl.'client/game/roadsheet', $params)->toArray();

		if ($response['array'] == null || $response == null || $response == false || $response['desc'] != "succeed"){
			throw new \Exception ("No ARP-Studio's game found", 500);
		}

        $array1 = array_column($response['array'], 'finishtime');
        array_multisort($array1, SORT_ASC, SORT_NATURAL|SORT_FLAG_CASE, $response['array']);

		$result = '';
		$data = [];

		foreach ($response['array'] as $key => $value ){
			$data[] = $response['array'][$key]['result'];
		}
		$result = implode(',', $data);

		return [
			"gameid" => $params['gameid'],
			"result" => $result,
		];
	}

    /**
     * Get all road sheet
     *
     * @param $params
     *
     * @return array
     */
	public function getAllRoadSheet($params)
	{
		$response = $this->makeRequest($this->baseurl.'client/game/all/roadsheet', $params)->toArray();

		if ($response['data'] == null || $response == null || $response == false || $response['desc'] != "succeed"){
			throw new \Exception ("No ARP-Studio's game found", 500);
		}

		array_multisort(array_column($response['array'], 'finishtime'), SORT_ASC, SORT_NATURAL|SORT_FLAG_CASE, $response['array']);

		$merging = [];

		foreach ($response['data'] as $key => $value) {
			$gameid = $key;
			$result = '';
			$data = [];

			if (!empty($value)) {
				array_multisort(array_column($response['data'][$key], 'finishtime'), SORT_ASC, SORT_NATURAL|SORT_FLAG_CASE, $response['data'][$key]);
				foreach($value as $key => $value){
					$data[] = $value['result'];
				}

				$result = implode(',', $data);
				$array = [
					"gameid" => $gameid,
					"result" => $result,
				];
				array_push($merging, $array);
			}
		}
		//Sort response based on natural order, ascending order
		sort($merging);
		return $merging;
	}

}
