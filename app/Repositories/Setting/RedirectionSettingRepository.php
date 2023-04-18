<?php


namespace App\Repositories\Setting;


use App\Constants\RedirectSettingConstant;
use App\Models\RedirectionSetting;
use App\Models\RedirectionSettingAccount;
use App\Repositories\BaseRepository;

class RedirectionSettingRepository extends BaseRepository
{

    public function model()
    {
        return RedirectionSetting::class;
    }

    public function getRedirectionSetting(array $params)
    {
        $groupIds = RedirectionSettingAccount::where('account_id', $params['account_id'])->get()->pluck('group_id');

        if (!empty($groupIds)) {
            $redirectSettings = $this->model->select(['redirect_from','redirect_to'])
                ->when($groupIds, function ($query) use ($groupIds){
                    return $query->whereIn('id', $groupIds);
                })
                ->where('status', RedirectSettingConstant::Active)
                ->when($params['path'], function ($query) use ($params) {
                    return $query->where('redirect_from', 'like', '%'.$params['path'].'%');
                })
                ->first();
        } else {
            $redirectSettings = $this->model->select(['redirect_from','redirect_to'])
                ->where('status', RedirectSettingConstant::Active)
                ->when($params['path'], function ($query) use ($params) {
                    return $query->where('redirect_from', 'like', '%'.$params['path'].'%');
                })
                ->where('default_fallback', true)
                ->first();
        }

        return $redirectSettings;
    }
}
