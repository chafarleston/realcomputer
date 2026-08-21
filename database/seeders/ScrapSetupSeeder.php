<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScrapSetupSeeder extends Seeder
{
    public function run(): void
    {
        $companies = DB::table('companies')->pluck('id');

        foreach ($companies as $companyId) {
            foreach (['venta' => 'Venta', 'compra' => 'Compra'] as $mode => $label) {
                $floorId = DB::table('floors')
                    ->where('company_id', $companyId)
                    ->where('name', $label)
                    ->value('id');

                if (!$floorId) {
                    $floorId = DB::table('floors')->insertGetId([
                        'company_id' => $companyId,
                        'name' => $label,
                        'order' => $mode === 'venta' ? 1 : 2,
                        'status' => 'ACTIVE',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                for ($i = 1; $i <= 5; $i++) {
                    $stationName = $label . ' ' . $i;
                    $exists = DB::table('restaurant_tables')
                        ->where('company_id', $companyId)
                        ->where('pos_mode', $mode)
                        ->where('name', $stationName)
                        ->exists();

                    if (!$exists) {
                        DB::table('restaurant_tables')->insert([
                            'company_id' => $companyId,
                            'floor_id' => $floorId,
                            'name' => $stationName,
                            'capacity' => 1,
                            'status' => 'AVAILABLE',
                            'is_for_kiosko' => false,
                            'pos_mode' => $mode,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            $serieExists = DB::table('series')
                ->where('company_id', $companyId)
                ->where('tipo_documento', 'CO')
                ->exists();

            if (!$serieExists) {
                DB::table('series')->insert([
                    'company_id' => $companyId,
                    'tipo_documento' => 'CO',
                    'serie' => 'COM',
                    'numero_actual' => 0,
                    'estado' => 'ACTIVO',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
