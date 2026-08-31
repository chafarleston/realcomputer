<?php

namespace App\Console\Commands;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Note;
use App\Models\PrintJob;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use App\Models\RestaurantTable;
use App\Models\Serie;
use App\Models\SpecialDocument;
use App\Models\SummaryDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetVentasStock extends Command
{
    protected $signature = 'sistema:reset-ventas {--force : Saltar la confirmación}';
    protected $description = 'Limpia ventas, compras, cajas, pedidos, resúmenes SUNAT y resetea stock a 0 (conserva productos/catálogo y el padrón SUNAT)';

    public function handle(): int
    {
        // Conserva productos, categorías, clientes, proveedores, usuarios,
        // empresas, mesas, series y el padrón SUNAT.
        $sections = [
            'invoice_items' => fn () => InvoiceItem::count(),
            'notes' => fn () => Note::count(),
            'special_document_items' => fn () => DB::table('special_document_items')->count(),
            'special_document_entities' => fn () => DB::table('special_document_entities')->count(),
            'special_documents' => fn () => SpecialDocument::count(),
            'summary_documents' => fn () => SummaryDocument::count(),
            'print_jobs' => fn () => PrintJob::count(),
            'cash_movements' => fn () => CashMovement::count(),
            'cashregisters' => fn () => CashRegister::count(),
            'restaurant_order_items' => fn () => RestaurantOrderItem::count(),
            'restaurant_orders' => fn () => RestaurantOrder::count(),
            'invoices' => fn () => Invoice::count(),
            'productos (stock a 0)' => fn () => DB::table('products')->count(),
            'series (numero a 0)' => fn () => Serie::count(),
            'mesas/estaciones' => fn () => RestaurantTable::count(),
        ];

        $this->info('============================================');
        $this->info('  RESET DEL SISTEMA (ventas, compras, cajas)');
        $this->info('============================================');
        $this->newLine();
        $this->line('Se eliminará:');
        foreach ($sections as $label => $count) {
            $this->line(sprintf('  - %-26s %d', $label, $count()));
        }
        $this->line('  - archivos físicos: storage/app/sunat/ (CDR) y storage/app/public/qrcodes/');
        $this->newLine();
        $this->line('Se conservará: productos, categorías, clientes, proveedores, usuarios,');
        $this->line('empresas, mesas, series y el padrón SUNAT.');
        $this->newLine();

        if (!$this->option('force') && !$this->confirm('¿Seguro que desea continuar? Esta acción NO se puede deshacer.', false)) {
            $this->warn('Operación cancelada.');
            return 0;
        }

        $this->info('Borrando...');

        DB::transaction(function () {
            InvoiceItem::query()->delete();
            Note::query()->delete();
            DB::table('special_document_items')->delete();
            DB::table('special_document_entities')->delete();
            SpecialDocument::query()->delete();
            SummaryDocument::query()->delete();
            PrintJob::query()->delete();
            CashMovement::query()->delete();
            CashRegister::query()->delete();
            RestaurantOrderItem::query()->delete();
            RestaurantOrder::query()->delete();
            Invoice::query()->delete();
        });

        // Reset stock de productos (conserva los productos)
        DB::table('products')->update(['stock' => 0]);

        // Reset numeración de series
        DB::table('series')->update(['numero_actual' => 0]);

        // Liberar mesas/estaciones
        RestaurantTable::query()->update(['status' => 'AVAILABLE', 'locked_by' => null, 'locked_at' => null]);

        // Archivos físicos de comprobantes (CDR) y códigos QR — conserva el padrón
        foreach (['sunat' => storage_path('app/sunat'), 'qrcodes' => storage_path('app/public/qrcodes')] as $name => $dir) {
            if (is_dir($dir)) {
                foreach (glob($dir . '/*') ?: [] as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
        }

        cache()->flush();

        $this->newLine();
        $this->info('=== COMPLETADO ===');
        $this->line('Ventas, compras, cajas, pedidos, resúmenes SUNAT, notas y cola de impresión eliminados.');
        $this->line('Stock de productos en 0. Series en 0. Mesas/estaciones libres.');
        $this->line('Archivos físicos de comprobantes y QR eliminados. Padrón SUNAT conservado.');
        $this->line('============================================');

        return 0;
    }
}