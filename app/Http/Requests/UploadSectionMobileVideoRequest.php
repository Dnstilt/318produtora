<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class UploadSectionMobileVideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // #region debug-point formrequest-authorize
        Log::debug('DEBUG: mobile-formrequest-authorize', [
            'user_authenticated' => $this->user() !== null,
            'user_is_admin' => $this->user()?->isAdmin() ?? false,
            'timestamp' => now()->toISOString(),
        ]);
        // #endregion
        
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // #region debug-point formrequest-validation
        Log::debug('DEBUG: mobile-formrequest-validation', [
            'file_exists' => $this->file('video') !== null,
            'file_valid' => $this->file('video')?->isValid(),
            'file_size' => $this->file('video')?->getSize(),
            'timestamp' => now()->toISOString(),
        ]);
        // #endregion
        
        return [
            'video' => [
                'required',
                'file',
                'max:102400',
                'mimes:mp4,webm,mov,mkv,avi',
            ],
        ];
    }
}
