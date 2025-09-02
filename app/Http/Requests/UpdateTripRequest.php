<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_address' => ['required_without_all:end_address,preferences', 'string'],
            'end_address' => ['required_without_all:start_address,preferences', 'string'],
            'preferences' => ['required_without_all:start_address,end_address', 'string'],
        ];
    }
}
