<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [$this->isMethod('PATCH') ? 'sometimes' : 'required', 'required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
