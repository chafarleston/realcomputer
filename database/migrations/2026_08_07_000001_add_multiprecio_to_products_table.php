<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('precio_venta_n2', 12, 4)->default(0)->after('precio');
            $table->decimal('precio_venta_n3', 12, 4)->default(0)->after('precio_venta_n2');
            $table->decimal('precio_venta_n4', 12, 4)->default(0)->after('precio_venta_n3');
            $table->decimal('precio_compra_n2', 12, 4)->default(0)->after('precio_compra');
            $table->decimal('precio_compra_n3', 12, 4)->default(0)->after('precio_compra_n2');
            $table->decimal('precio_compra_n4', 12, 4)->default(0)->after('precio_compra_n3');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'precio_venta_n2',
                'precio_venta_n3',
                'precio_venta_n4',
                'precio_compra_n2',
                'precio_compra_n3',
                'precio_compra_n4',
            ]);
        });
    }
};
