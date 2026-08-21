<?php

namespace Database\Seeders;

use App\Models\Printer;
use Illuminate\Database\Seeder;

class PrinterSeeder extends Seeder
{
    protected array $slots = [
        ['assigned_to' => 'productos', 'name' => 'Productos', 'type' => 'local', 'port' => 9100],
        ['assigned_to' => 'precuenta', 'name' => 'Precuenta', 'type' => 'local', 'port' => 9100],
        ['assigned_to' => 'caja', 'name' => 'Caja', 'type' => 'local', 'port' => 9100],
    ];

    public function run(): void
    {
        foreach ($this->slots as $slot) {
            Printer::firstOrCreate(
                ['assigned_to' => $slot['assigned_to']],
                $slot
            );
        }
    }
}
