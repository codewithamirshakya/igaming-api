<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\RedirectSettingRequest;
use App\Http\Resources\FaqSettingResource;
use App\Services\Setting\SettingService;

class SettingController extends Controller
{
    /**
     * @var SettingService
     */
    private SettingService $settingService;

    /**
     * SettingController constructor.
     * @param SettingService $settingService
     */
    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Get maintenance
     *
     * @return array|mixed
     * @throws \Exception
     */
    public function getMaintenance() : ?array
    {
        return $this->settingService->getFrontendSettings();
    }

    /**
     * Get faq settings
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getFaqSettings() : ?object
    {
        return FaqSettingResource::collection($this->settingService->getFaqSettings());
    }

    /**
     * Get redirect settings
     *
     * @param RedirectSettingRequest $request
     * @return string|null
     */
    public function getRedirectSettings(RedirectSettingRequest $request) : ?string
    {
        return $this->settingService->getRedirectSettings($request->all());
    }
}
