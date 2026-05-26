<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string'],
        ];
    }
}

