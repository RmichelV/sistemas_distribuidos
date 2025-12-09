<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    // Listar todas las configuraciones
    public function index(Request $request)
    {
        $query = SystemSetting::query();

        // Filtrar por categoría
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filtrar solo públicas
        if ($request->has('public_only') && $request->public_only) {
            $query->public();
        }

        // Buscar por clave
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('key', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $settings = $query->orderBy('category')->orderBy('key')->paginate(15);

        return response()->json($settings);
    }

    // Obtener todas las configuraciones públicas
    public function publicSettings()
    {
        $settings = SystemSetting::public()
            ->orderBy('category')
            ->orderBy('key')
            ->get()
            ->map(function ($setting) {
                return [
                    'key' => $setting->key,
                    'value' => $setting->typed_value,
                    'type' => $setting->type,
                    'category' => $setting->category
                ];
            });

        return response()->json($settings);
    }

    // Obtener configuración por clave
    public function getByKey($key)
    {
        $setting = SystemSetting::where('key', $key)->first();

        if (!$setting) {
            return response()->json(['message' => 'Configuración no encontrada'], 404);
        }

        return response()->json([
            'key' => $setting->key,
            'value' => $setting->typed_value,
            'type' => $setting->type,
            'category' => $setting->category,
            'description' => $setting->description
        ]);
    }

    // Obtener configuraciones por categoría
    public function getByCategory($category)
    {
        $settings = SystemSetting::byCategory($category)
            ->orderBy('key')
            ->get()
            ->map(function ($setting) {
                return [
                    'id' => $setting->id,
                    'key' => $setting->key,
                    'value' => $setting->typed_value,
                    'type' => $setting->type,
                    'description' => $setting->description,
                    'is_public' => $setting->is_public
                ];
            });

        return response()->json($settings);
    }

    // Ver detalles de una configuración
    public function show($id)
    {
        $setting = SystemSetting::find($id);

        if (!$setting) {
            return response()->json(['message' => 'Configuración no encontrada'], 404);
        }

        return response()->json($setting);
    }

    // Crear nueva configuración
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|unique:system_settings,key|max:255',
            'value' => 'required',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $setting = new SystemSetting();
        $setting->key = $request->key;
        $setting->value = $request->value; // El mutator se encarga del tipo
        $setting->category = $request->category;
        $setting->description = $request->description;
        $setting->is_public = $request->is_public ?? false;
        $setting->save();

        return response()->json($setting, 201);
    }

    // Actualizar configuración
    public function update(Request $request, $id)
    {
        $setting = SystemSetting::find($id);

        if (!$setting) {
            return response()->json(['message' => 'Configuración no encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'sometimes|string|unique:system_settings,key,' . $id . '|max:255',
            'value' => 'sometimes|required',
            'category' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('key')) {
            $setting->key = $request->key;
        }
        if ($request->has('value')) {
            $setting->value = $request->value;
        }
        if ($request->has('category')) {
            $setting->category = $request->category;
        }
        if ($request->has('description')) {
            $setting->description = $request->description;
        }
        if ($request->has('is_public')) {
            $setting->is_public = $request->is_public;
        }
        
        $setting->save();

        return response()->json($setting);
    }

    // Actualizar valor por clave (método simplificado)
    public function updateByKey(Request $request, $key)
    {
        $setting = SystemSetting::where('key', $key)->first();

        if (!$setting) {
            return response()->json(['message' => 'Configuración no encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $setting->value = $request->value;
        $setting->save();

        return response()->json([
            'key' => $setting->key,
            'value' => $setting->typed_value,
            'type' => $setting->type
        ]);
    }

    // Eliminar configuración
    public function destroy($id)
    {
        $setting = SystemSetting::find($id);

        if (!$setting) {
            return response()->json(['message' => 'Configuración no encontrada'], 404);
        }

        $setting->delete();

        return response()->json(['message' => 'Configuración eliminada correctamente']);
    }

    // Obtener lista de categorías
    public function categories()
    {
        $categories = SystemSetting::select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json($categories);
    }

    // Actualización masiva por categoría
    public function bulkUpdateByCategory(Request $request, $category)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updated = 0;
        foreach ($request->settings as $settingData) {
            $setting = SystemSetting::where('category', $category)
                ->where('key', $settingData['key'])
                ->first();
            
            if ($setting) {
                $setting->value = $settingData['value'];
                $setting->save();
                $updated++;
            }
        }

        return response()->json([
            'message' => "Se actualizaron {$updated} configuraciones",
            'updated_count' => $updated
        ]);
    }
}
