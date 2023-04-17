<?php


namespace App\Repositories\Setting;


use App\Constants\FaqSettingConstant;
use App\Models\FaqSetting;
use App\Repositories\BaseRepository;

class FaqSettingRepository extends BaseRepository
{
    /**
     * Initialize model
     *
     * @return string
     */
    public function model()
    {
        return FaqSetting::class;
    }

    /**
     * Get faq settings collections
     *
     * @return mixed
     */
    public function getFaqSettings()
    {
        return $this->model
            ->where('status', FaqSettingConstant::Active)
            ->orderBy('sequence_id','ASC')
            ->get();
    }
}
