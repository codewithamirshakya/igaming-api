<?php

namespace App\Http\Controllers\ArpStudio;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArpStudio\EnteringRequest;
use App\Http\Requests\ArpStudio\ExitingRequest;
use App\Http\Requests\ArpStudio\GameListRequest;
use App\Http\Requests\ArpStudio\PromoInRequest;
use App\Http\Requests\ArpStudio\RoadTrendRequest;
use App\Services\Arpstudio\ArpstudioService;
use App\Services\Arpstudio\GameListService;
use App\Services\Arpstudio\RoadTrendService;
use Illuminate\Http\Request;

class ArpStudioController extends Controller
{
    public $arpStudioService;
    public $gameListService;
    public $roadTrendService;

    public function __construct(ArpstudioService $arpStudioService, GameListService $gameListService, RoadTrendService $roadTrendService)
    {
        $this->arpStudioService = $arpStudioService;
        $this->gameListService  = $gameListService;
        $this->roadTrendService = $roadTrendService;
    }

    /**
     * Deposit SiG Funds into ARPStudio
     * 
     * @param EnteringRequest $request
     */
    public function entering(EnteringRequest $request)
    {
        $this->arpStudioService->entering($request->all());
    }

    /**
     * Withdraw SiG Funds from ARPStudio
     * 
     * @param ExitingRequest $request
     */
    public function exiting(ExitingRequest $request)
    {
        $this->arpStudioService->exiting($request->all());
    }

    /**
     * Deposit Promo Credit into ARPStudio
     * 
     * @param PromoInRequest $request
     */
    public function promoIn(PromoInRequest $request)
    {
        $this->arpStudioService->promoIn($request->all());
    }

    /**
     * Get studio game list
     * 
     * @param GameListRequest $request
     */
    public function gameList(GameListRequest $request)
    {
        $this->gameListService->gameList($request->all());
    }

    /**
     * Get studio road trend
     * 
     */
    public function roadTrend(RoadTrendRequest $request)
    {
        $this->roadTrendService->roadTrend($request->all());
    }
}
