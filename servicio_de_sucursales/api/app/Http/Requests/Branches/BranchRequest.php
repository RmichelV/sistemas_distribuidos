<?php

namespace App\Http\Requests\Branches;

use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');
        
        return [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'address' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la sucursal es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'address.required' => 'La dirección es obligatoria.',
            'address.string' => 'La dirección debe ser texto.',
            'address.max' => 'La dirección no puede exceder 255 caracteres.',
        ];
    }
}
