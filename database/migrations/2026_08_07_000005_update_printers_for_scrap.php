<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('printers')->where('assigned_to', 'cocina-1')->update([
            'assigned_to' => 'productos',
            'name' => 'Productos',
        ]);

        DB::table('printers')
            ->whereIn('assigned_to', ['cocina-2', 'bar-1', 'autopedido', 'precuenta2', 'precuenta3'])
            ->delete();
    }

    public function down(): void
    {
        DB::table('printers')->where('assigned_to', 'productos')->update([
            'assigned_to' => 'cocina-1',
            'name' => 'Cocina 1',
        ]);
    }
};
