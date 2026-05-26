<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePhotoRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->expectsJson() || $this->isJson()) {
            return [
                'orders' => ['required', 'array'],
                'orders.*' => ['integer'],
            ];
        }

        return [
            'photo' => ['required', 'image', 'max:20480'],
        ];
    }
}
