<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialLinkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}

