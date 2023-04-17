<?php

namespace App\Repositories\Session;

use App\Models\Session;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SessionRepository extends BaseRepository
{

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Session::class;
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
     * Get platform session
     * 
     * @param int $userId
     * @param int $platformId
     * @return Model
     */
    public function getPlatformSession(int $userId, int $platformId) : Model
    {
        return $this->model
                ->where('userid', $userId)
                ->where('platformid', $platformId)
                ->first();
    }
    
    /**
     * Get sesssion status
     * 
     * @param $userId
     * @param $platformId
     * @return string
     */
    public function getSessionStatus(int $userId, int $platformId) : string
    {
        $platformSession = $this->getPlatformSession($userId, $platformId);

        if (empty($platformSession)) {
            return 'no_session';
        }

        if ($platformSession->transferid != 0) {
            return 'transfer_pending';
        }

        if ($platformSession->active == 0) {
            return 'inactive';
        }
    }

}
