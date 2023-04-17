<?php

namespace App\Repositories\Session;

use App\Models\PlatformExchange;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PlatformExchangeRepository extends BaseRepository
{

    /**
     * Configure the Model
     **/
    public function model()
    {
        return PlatformExchange::class;
    }

    /**
     * Returns all the model with pagination
     *
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedList(Request $request)
    : LengthAwarePaginator
    {
        $limit = $request->get('limit', config('app.per_page'));
        return $this->model->newQuery()->paginate($limit);
    }

}
