<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')->update(['print_destination' => 'productos']);
        DB::table('restaurant_order_items')->update(['print_destination' => 'productos']);

        DB::statement("ALTER TABLE products MODIFY print_destination VARCHAR(20) NOT NULL DEFAULT 'productos'");
        DB::statement("ALTER TABLE restaurant_order_items MODIFY print_destination VARCHAR(20) NOT NULL DEFAULT 'productos'");

        DB::table('printers')->whereIn('assigned_to', ['despacho_compra', 'despacho_venta'])->delete();
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE products MODIFY print_destination VARCHAR(20) NOT NULL DEFAULT 'productos'");
        DB::statement("ALTER TABLE restaurant_order_items MODIFY print_destination VARCHAR(20) NOT NULL DEFAULT 'productos'");
    }
};
