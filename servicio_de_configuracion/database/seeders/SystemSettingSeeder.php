<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Configuraciones generales
            [
                'key' => 'app.name',
                'value' => 'Sistema de Ventas E-WTTO',
                'category' => 'general',
                'description' => 'Nombre de la aplicación',
                'is_public' => true
            ],
            [
                'key' => 'app.timezone',
                'value' => 'America/La_Paz',
                'category' => 'general',
                'description' => 'Zona horaria del sistema',
                'is_public' => true
            ],
            [
                'key' => 'app.locale',
                'value' => 'es',
                'category' => 'general',
                'description' => 'Idioma del sistema',
                'is_public' => true
            ],
            
            // Configuraciones de negocio
            [
                'key' => 'business.tax_rate',
                'value' => 0.13,
                'category' => 'business',
                'description' => 'Tasa de impuesto (IVA 13%)',
                'is_public' => true
            ],
            [
                'key' => 'business.currency',
                'value' => 'BOB',
                'category' => 'business',
                'description' => 'Moneda principal del sistema',
                'is_public' => true
            ],
            [
                'key' => 'business.working_hours_start',
                'value' => '08:00',
                'category' => 'business',
                'description' => 'Hora de inicio de jornada laboral',
                'is_public' => false
            ],
            [
                'key' => 'business.working_hours_end',
                'value' => '18:00',
                'category' => 'business',
                'description' => 'Hora de fin de jornada laboral',
                'is_public' => false
            ],
            [
                'key' => 'business.max_discount_percentage',
                'value' => 20,
                'category' => 'business',
                'description' => 'Porcentaje máximo de descuento permitido',
                'is_public' => false
            ],
            
            // Configuraciones de inventario
            [
                'key' => 'inventory.low_stock_threshold',
                'value' => 10,
                'category' => 'inventory',
                'description' => 'Umbral de stock bajo',
                'is_public' => false
            ],
            [
                'key' => 'inventory.enable_negative_stock',
                'value' => false,
                'category' => 'inventory',
                'description' => 'Permitir stock negativo',
                'is_public' => false
            ],
            [
                'key' => 'inventory.auto_reorder',
                'value' => true,
                'category' => 'inventory',
                'description' => 'Activar reorden automático',
                'is_public' => false
            ],
            
            // Configuraciones de ventas
            [
                'key' => 'sales.require_customer',
                'value' => false,
                'category' => 'sales',
                'description' => 'Requerir cliente en todas las ventas',
                'is_public' => false
            ],
            [
                'key' => 'sales.allow_credit',
                'value' => true,
                'category' => 'sales',
                'description' => 'Permitir ventas a crédito',
                'is_public' => false
            ],
            [
                'key' => 'sales.max_credit_days',
                'value' => 30,
                'category' => 'sales',
                'description' => 'Días máximos de crédito',
                'is_public' => false
            ],
            [
                'key' => 'sales.receipt_footer_text',
                'value' => 'Gracias por su compra',
                'category' => 'sales',
                'description' => 'Texto al pie del recibo',
                'is_public' => false
            ],
            
            // Configuraciones de reservaciones
            [
                'key' => 'reservations.max_days_advance',
                'value' => 90,
                'category' => 'reservations',
                'description' => 'Días máximos de anticipación para reservas',
                'is_public' => true
            ],
            [
                'key' => 'reservations.cancellation_hours',
                'value' => 24,
                'category' => 'reservations',
                'description' => 'Horas de anticipación para cancelar reserva',
                'is_public' => true
            ],
            [
                'key' => 'reservations.deposit_percentage',
                'value' => 50,
                'category' => 'reservations',
                'description' => 'Porcentaje de depósito requerido',
                'is_public' => false
            ],
            
            // Configuraciones de recursos humanos
            [
                'key' => 'hr.probation_period_days',
                'value' => 90,
                'category' => 'hr',
                'description' => 'Período de prueba en días',
                'is_public' => false
            ],
            [
                'key' => 'hr.vacation_days_per_year',
                'value' => 15,
                'category' => 'hr',
                'description' => 'Días de vacación por año',
                'is_public' => false
            ],
            [
                'key' => 'hr.overtime_multiplier',
                'value' => 1.5,
                'category' => 'hr',
                'description' => 'Multiplicador de horas extra',
                'is_public' => false
            ],
            
            // Configuraciones de notificaciones
            [
                'key' => 'notifications.email_enabled',
                'value' => true,
                'category' => 'notifications',
                'description' => 'Habilitar notificaciones por email',
                'is_public' => false
            ],
            [
                'key' => 'notifications.sms_enabled',
                'value' => false,
                'category' => 'notifications',
                'description' => 'Habilitar notificaciones por SMS',
                'is_public' => false
            ],
            
            // Configuraciones de seguridad
            [
                'key' => 'security.session_timeout_minutes',
                'value' => 120,
                'category' => 'security',
                'description' => 'Tiempo de expiración de sesión en minutos',
                'is_public' => false
            ],
            [
                'key' => 'security.password_min_length',
                'value' => 8,
                'category' => 'security',
                'description' => 'Longitud mínima de contraseña',
                'is_public' => false
            ],
            [
                'key' => 'security.max_login_attempts',
                'value' => 5,
                'category' => 'security',
                'description' => 'Intentos máximos de inicio de sesión',
                'is_public' => false
            ]
        ];

        foreach ($settings as $setting) {
            SystemSetting::create($setting);
        }
    }
}
