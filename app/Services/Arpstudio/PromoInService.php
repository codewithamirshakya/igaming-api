<?php 

namespace App\Services\Arpstudio;

use App\Models\Game;
use Illuminate\Support\Facades\Http;

class PromoInService extends ArpstudioService
{
	public function promoIn(array $params)
	{
		$this->platformAddPromoCredit($params['username'], $params['amount']);
	}
}