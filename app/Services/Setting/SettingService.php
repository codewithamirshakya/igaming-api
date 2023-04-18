<?php


namespace App\Services\Setting;

use App\Constants\RedirectSettingConstant;
use App\Models\RedirectionSetting;
use App\Models\RedirectionSettingAccount;
use App\Repositories\Setting\FaqSettingRepository;
use App\Repositories\Setting\RedirectionSettingRepository;
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
     * @var RedirectionSettingRepository
     */
    private $redirectionSettingRepository;

    /**
     * SettingService constructor.
     * @param SettingRepository $settingRepository
     * @param FaqSettingRepository $faqSettingRepository
     * @param RedirectionSettingRepository $redirectionSettingRepository
     */
    public function __construct(
        SettingRepository $settingRepository,
        FaqSettingRepository $faqSettingRepository,
        RedirectionSettingRepository $redirectionSettingRepository
    )
    {
        $this->settingRepository                = $settingRepository;
        $this->faqSettingRepository             = $faqSettingRepository;
        $this->redirectionSettingRepository     = $redirectionSettingRepository;
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
        $redirectSettings = $this->redirectionSettingRepository->getRedirectionSetting($params);

        return $redirectSettings->redirect_from ?? null;

    }
}
