<?php

namespace App\Http\Requests\ArpStudio;

use Illuminate\Foundation\Http\FormRequest;

class EnteringRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account'           => ['required', 'integer'],
            'un'                => ['required', 'integer'],
            'key'               => ['required', 'string'],
            'gameid'            => ['integer'],
            'validateKey'       => ['boolean'],
            'ip'                => ['ip'],
            'language'          => ['string'],
            'currency'          => ['string'],
            'currenttime'       => ['string'],
            'piexl'             => ['string'],
            'potrait'           => ['string'],
            'returnurl'         => ['string'],
            'nickname'          => ['string'],
            'wc_version'        => ['numeric']
        ];
    }
}
