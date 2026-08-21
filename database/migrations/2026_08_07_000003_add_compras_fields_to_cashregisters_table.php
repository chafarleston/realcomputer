<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashregisters', function (Blueprint $table) {
            $table->decimal('compras_efectivo', 12, 2)->default(0)->after('ventas_otro');
            $table->decimal('compras_tarjeta', 12, 2)->default(0)->after('compras_efectivo');
            $table->decimal('compras_yape', 12, 2)->default(0)->after('compras_tarjeta');
            $table->decimal('compras_plin', 12, 2)->default(0)->after('compras_yape');
            $table->decimal('compras_otro', 12, 2)->default(0)->after('compras_plin');
            $table->integer('cantidad_compras')->default(0)->after('compras_otro');
            $table->decimal('total_compras', 12, 2)->default(0)->after('cantidad_compras');
            $table->decimal('ingresos_total', 12, 2)->default(0)->after('total_compras');
            $table->decimal('gastos_total', 12, 2)->default(0)->after('ingresos_total');
            $table->decimal('saldo_final', 12, 2)->default(0)->after('gastos_total');
        });
    }

    public function down(): void
    {
        Schema::table('cashregisters', function (Blueprint $table) {
            $table->dropColumn([
                'compras_efectivo',
                'compras_tarjeta',
                'compras_yape',
                'compras_plin',
                'compras_otro',
                'cantidad_compras',
                'total_compras',
                'ingresos_total',
                'gastos_total',
                'saldo_final',
            ]);
        });
    }
};
