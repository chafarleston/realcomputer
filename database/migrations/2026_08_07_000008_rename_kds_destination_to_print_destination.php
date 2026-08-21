<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('kds_destination', 'print_destination');
        });
        Schema::table('restaurant_order_items', function (Blueprint $table) {
            $table->renameColumn('kds_destination', 'print_destination');
        });

        DB::table('products')
            ->whereIn('print_destination', ['cocina', 'cocina2', 'bar'])
            ->update(['print_destination' => 'despacho_compra']);
        DB::table('restaurant_order_items')
            ->whereIn('print_destination', ['cocina', 'cocina2', 'bar'])
            ->update(['print_destination' => 'despacho_compra']);

        DB::statement("ALTER TABLE products MODIFY print_destination VARCHAR(20) NOT NULL DEFAULT 'despacho_compra'");
        DB::statement("ALTER TABLE restaurant_order_items MODIFY print_destination VARCHAR(20) NOT NULL DEFAULT 'despacho_compra'");
    }

    public function down(): void
    {
        DB::table('products')->update(['print_destination' => 'cocina']);
        DB::table('restaurant_order_items')->update(['print_destination' => 'cocina']);

        DB::statement("ALTER TABLE products MODIFY kds_destination VARCHAR(20) NOT NULL DEFAULT 'cocina'");
        DB::statement("ALTER TABLE restaurant_order_items MODIFY kds_destination VARCHAR(20) NOT NULL DEFAULT 'cocina'");

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('print_destination', 'kds_destination');
        });
        Schema::table('restaurant_order_items', function (Blueprint $table) {
            $table->renameColumn('print_destination', 'kds_destination');
        });
    }
};
