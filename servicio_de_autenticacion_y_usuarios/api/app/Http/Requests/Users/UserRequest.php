<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Validator;

class UserRequest extends FormRequest
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
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');
        
        $rules = [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255', 'regex:/^[\pL\s.,-]+$/u'],
            'address' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255', 'regex:/^[\pL\s\d.,#-]+$/u'],
            'phone' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'digits_between:8,15'],
            'role_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:roles,id'],
            
            // branch_id: Validación básica (integer), existencia se valida en after()
            'branch_id' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            
            'base_salary' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:500'],
            'hire_date' => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'email' => [$isUpdate ? 'sometimes' : 'required', 'string', 'email', 'max:255', 'unique:users,email', 'regex:/@ewtto\.com$/i'],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];

        // Si es actualización (PUT/PATCH), ignorar el email del usuario actual
        if ($isUpdate) {
            $userId = $this->route('id') ?? $this->route('user');
            $rules['email'] = ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,'.$userId, 'regex:/@ewtto\.com$/i'];
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     * 
     * Validación de branch_id mediante HTTP request al branch-service
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->has('branch_id')) {
                    try {
                        $branchServiceUrl = env('BRANCH_SERVICE_URL', 'http://branch_api');
                        $response = Http::timeout(5)->get("{$branchServiceUrl}/api/branches/{$this->branch_id}");
                        
                        if (!$response->successful()) {
                            $validator->errors()->add(
                                'branch_id',
                                'La sucursal seleccionada no existe o no está disponible.'
                            );
                        }
                    } catch (\Exception $e) {
                        // Si el servicio no está disponible, loguear error pero permitir creación
                        \Log::warning('Branch service unavailable during user validation', [
                            'branch_id' => $this->branch_id,
                            'error' => $e->getMessage()
                        ]);
                        
                        // En desarrollo, podrías querer fallar la validación:
                        // $validator->errors()->add('branch_id', 'No se pudo validar la sucursal. Servicio no disponible.');
                    }
                }
            }
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede exceder los 255 caracteres.',
            'name.regex' => 'El nombre solo puede contener letras, espacios y signos básicos de puntuación.',

            'address.required' => 'La dirección es obligatoria.',
            'address.max' => 'La dirección no puede exceder los 255 caracteres.',
            'address.regex' => 'La dirección contiene caracteres inválidos.',

            'phone.required' => 'El número de teléfono es obligatorio.',
            'phone.numeric' => 'El número de teléfono debe contener solo dígitos.',
            'phone.digits_between' => 'El teléfono debe tener entre 8 y 15 dígitos.',
            
            'role_id.required' => 'El rol del usuario es obligatorio.',
            'role_id.integer' => 'El ID del rol debe ser un número entero.',
            'role_id.exists' => 'El rol seleccionado no es válido.',

            'branch_id.required' => 'La sucursal es obligatoria.',
            'branch_id.integer' => 'El ID de la sucursal debe ser un número entero.',

            'base_salary.required' => 'El salario base es obligatorio.',
            'base_salary.numeric' => 'El salario base debe ser un valor numérico.',
            'base_salary.min' => 'El salario base debe ser al menos 500.',

            'hire_date.required' => 'La fecha de contratación es obligatoria.',
            'hire_date.date' => 'El formato de la fecha de contratación no es válido.',

            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe tener un formato válido (ej. correo@ewtto.com).',
            'email.max' => 'El email no puede exceder los 255 caracteres.',
            'email.unique' => 'Este email ya se encuentra registrado en el sistema.',
            'email.regex' => 'El email debe pertenecer al dominio @ewtto.com.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];
    }
}
