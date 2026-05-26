<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadSectionVideoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'video' => [
                'required',
                'file',
                'max:512000',
                'mimes:mp4,webm,mov,mkv,avi',
            ],
        ];
    }
}
