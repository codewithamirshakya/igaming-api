<?php


namespace App\Repositories\Setting;


use App\Models\Setting;
use App\Repositories\BaseRepository;

class SettingRepository extends BaseRepository
{

    public function model()
    {
        return Setting::class;
    }

    /**
     * Get settings by attributes
     *
     * @param $attributes
     * @return mixed
     */
    public function getSettings(array $attributes)
    {
        return $this->model->select(['name','data'])->whereIn('name', $attributes)->get();
    }
}
