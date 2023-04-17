<?php


namespace App\Services\Setting;

use App\Constants\RedirectSettingConstant;
use App\Models\RedirectionSetting;
use App\Models\RedirectionSettingAccount;
use App\Repositories\Setting\FaqSettingRepository;
use App\Repositories\Setting\SettingRepository;
use App\Repositories\Setting\RedirectSettingRepository;
use Carbon\Carbon;

class SettingService
{
    private $settingRepository;
    private $frontendSettings = ['frontend_off','frontend_maintenance','frontend_maintenance_start','frontend_maintenance_end','frontend_maintenance_message','frontend_show_date','frontend_force_logout'];
    /**
     * @var FaqSettingRepository
     */
    private $faqSettingRepository;

    /**
     * SettingService constructor.
     * @param SettingRepository $settingRepository
     * @param FaqSettingRepository $faqSettingRepository
     */
    public function __construct(
        SettingRepository $settingRepository,
        FaqSettingRepository $faqSettingRepository
    )
    {
        $this->settingRepository            = $settingRepository;
        $this->faqSettingRepository         = $faqSettingRepository;
    }

    /**
     * Get frontend settings
     *
     * @return mixed
     * @throws \Exception
     */
    public function getFrontendSettings()
    {
        $settings = $this->settingRepository
                        ->getSettings($this->frontendSettings)
                        ->pluck('data','name');

        $response = [];
        foreach ($settings as $name => $data)
        {
            if (in_array($name, ['frontend_maintenance_start','frontend_maintenance_end'])) {
                $date = new Carbon($data);

                //reformat the maintenance start and end date to this format, Sunday, 24 January 2022 4:00AM
                $data = $date->format('l, j F Y g:iA');
            }

            $response[$name] = $data;
        }

        return $response;
    }

    /**
     * Get faq settings service
     *
     * @return mixed
     */
    public function getFaqSettings()
    {
        return $this->faqSettingRepository->getFaqSettings();
    }

    /**
     * Get redirect settings
     *
     * @param array $params
     * @return string|null
     */
    public function getRedirectSettings(array $params)
    {
        $groupIds = RedirectionSettingAccount::where('account_id', $params['account_id'])->get()->pluck('group_id');

        if (!empty($groupIds)) {
            $redirectSettings = RedirectionSetting::
                select(['redirect_from','redirect_to'])
                ->when($groupIds, function ($query) use ($groupIds){
                    return $query->whereIn('id', $groupIds);
                })
                ->where('status', RedirectSettingConstant::Active)
                ->when($params['path'], function ($query) use ($params) {
                    return $query->where('redirect_from', 'like', '%'.$params['path'].'%');
                })
                ->first();
        } else {
            $redirectSettings = RedirectionSetting::
                select(['redirect_from','redirect_to'])
                ->where('status', RedirectSettingConstant::Active)
                ->when($params['path'], function ($query) use ($params) {
                    return $query->where('redirect_from', 'like', '%'.$params['path'].'%');
                })
                ->where('default_fallback', true)
                ->first();
        }


        return $redirectSettings->redirect_from ?? null;

    }
}
