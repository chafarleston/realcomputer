<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE invoices MODIFY sunat_estado ENUM('PENDIENTE', 'ENVIADO', 'ACEPTADO', 'RECHAZADO', 'ANULADO', 'NO_ENVIADO') NOT NULL DEFAULT 'PENDIENTE'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE invoices MODIFY sunat_estado ENUM('PENDIENTE', 'ENVIADO', 'ACEPTADO', 'RECHAZADO', 'ANULADO') NOT NULL DEFAULT 'PENDIENTE'");
    }
};
