<?php

namespace App\Repositories\Session;

use App\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class UserRepository extends BaseRepository
{

    /**
     * Configure the Model
     **/
    public function model()
    {
        return User::class;
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

    /**
     * Get user by attribute
     * 
     * @param string $attribute
     * @param string $value
     * @return Model
     */
    public function getUserByAttribute($attribute, $value)
    {
        return $this->model->where($attribute, $value)->first();
    }

}
