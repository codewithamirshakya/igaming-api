<?php

namespace App\Helpers\Response;

trait Response {

    /**
     * Prepare Success response
     *
     * @param $message
     * @param $code
     * @return mixed
     */
    protected function prepareResponse($message, $code)
    {
        return [
            'data'  => $message,
            'code'  => $code
        ];
    }

    /**
     * Prepare Error response
     *
     * @param $message
     * @param $code
     * @param $errors
     * @return mixed
     */
    protected function prepareErrorResponse($message, $code, $errors)
    {
        return [
            'message'   => $message,
            'code'      => $code,
            'error'     => $errors
        ];
    }

    /**
     * Success response
     *
     * @param $message
     * @param $code
     *
     * @return mixed
     */
    public function successResponse($message, $code)
    {
        return response($this->prepareResponse($message, $code), $code);
    }

    /**
     * Error response
     *
     * @param $message
     * @param int $code
     * @param null $errors
     * @return mixed
     */
    public function errorResponse($message, $code = 400, $errors = null)
    {
        return response($this->prepareErrorResponse($message, $code, $errors), $code);
    }

}
