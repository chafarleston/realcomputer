<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $slots = [
            ['assigned_to' => 'despacho_compra', 'name' => 'Despacho de Compras', 'type' => 'local', 'port' => 9100],
            ['assigned_to' => 'despacho_venta', 'name' => 'Despacho de Venta', 'type' => 'local', 'port' => 9100],
        ];

        foreach ($slots as $slot) {
            $exists = DB::table('printers')->where('assigned_to', $slot['assigned_to'])->exists();
            if (!$exists) {
                DB::table('printers')->insert(array_merge($slot, [
                    'printer_name' => null,
                    'ip_address' => null,
                    'active' => true,
                    'paper_size' => '80mm',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        DB::table('printers')->whereIn('assigned_to', ['despacho_compra', 'despacho_venta'])->delete();
    }
};
