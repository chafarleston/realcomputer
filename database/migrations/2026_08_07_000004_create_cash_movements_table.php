<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('cash_register_id')->constrained('cashregisters')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('tipo', ['INGRESO', 'GASTO'])->default('INGRESO');
            $table->decimal('monto', 12, 2)->default(0);
            $table->string('concepto', 255)->nullable();
            $table->dateTime('fecha')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
