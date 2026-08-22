<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('permission', 'view_invoices');

        $companyId = Company::getMainCompany()->id;

        $tipo = $request->get('tipo', 'venta');
        $periodo = $request->get('periodo', 'diario');
        $fecha = $request->get('fecha') ? Carbon::parse($request->get('fecha')) : Carbon::today();
        $seleccion = $request->get('seleccion', 'todos');
        $categoriaId = $request->get('categoria_id');
        $productoIds = (array) $request->get('productos', []);

        [$desde, $hasta, $siguiente, $anterior] = $this->resolvePeriodo($periodo, $fecha);

        $productos = Product::where('company_id', $companyId)
            ->where('estado', 'ACTIVO')
            ->orderBy('descripcion')
            ->get();

        $categorias = Category::where('company_id', $companyId)
            ->whereIn('estado', ['ACTIVO', 'ACT'])
            ->orderBy('nombre')
            ->get();

        $base = fn ($q) => DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('invoices.company_id', $companyId)
            ->where('invoices.sunat_estado', '!=', 'ANULADO')
            ->whereBetween('invoices.fecha_emision', [$desde, $hasta])
            ->when($tipo !== 'compra', fn ($q) => $q->where('invoices.tipo_documento', '!=', 'CO'))
            ->when($tipo === 'compra', fn ($q) => $q->where('invoices.tipo_documento', 'CO'))
            ->when($seleccion === 'categoria' && $categoriaId, fn ($q) => $q->where('products.category_id', $categoriaId))
            ->when($seleccion === 'productos' && count($productoIds) > 0, fn ($q) => $q->whereIn('invoice_items.product_id', $productoIds));

        $totales = (clone $base(null))->selectRaw(
            'COUNT(DISTINCT invoices.id) as documentos,
             SUM(invoice_items.cantidad) as cantidad,
             SUM(invoice_items.precio_venta) as importe,
             SUM(invoice_items.igv) as igv'
        )->first();

        $porProducto = (clone $base(null))
            ->selectRaw(
                'invoice_items.product_id,
                 products.codigo,
                 products.descripcion,
                 categories.nombre as categoria,
                 SUM(invoice_items.cantidad) as cantidad,
                 SUM(invoice_items.precio_venta) as importe,
                 COUNT(DISTINCT invoice_items.invoice_id) as documentos'
            )
            ->groupBy('invoice_items.product_id', 'products.codigo', 'products.descripcion', 'categories.nombre')
            ->orderByRaw('importe DESC')
            ->get();

        $porFecha = (clone $base(null))
            ->selectRaw(
                'invoices.fecha_emision,
                 COUNT(DISTINCT invoices.id) as documentos,
                 SUM(invoice_items.precio_venta) as importe'
            )
            ->groupBy('invoices.fecha_emision')
            ->orderBy('invoices.fecha_emision')
            ->get();

        $documentos = DB::table('invoices')
            ->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
            ->where('invoices.company_id', $companyId)
            ->where('invoices.sunat_estado', '!=', 'ANULADO')
            ->whereBetween('invoices.fecha_emision', [$desde, $hasta])
            ->when($tipo !== 'compra', fn ($q) => $q->where('invoices.tipo_documento', '!=', 'CO'))
            ->when($tipo === 'compra', fn ($q) => $q->where('invoices.tipo_documento', 'CO'))
            ->select(
                'invoices.id',
                \Illuminate\Support\Facades\DB::raw("CONCAT(invoices.serie, '-', LPAD(invoices.numero, 8, '0')) as full_number"),
                'invoices.tipo_documento',
                'invoices.fecha_emision',
                'invoices.hora_emision',
                'invoices.metodo_pago',
                'invoices.total',
                'customers.nombre as cliente'
            )
            ->orderBy('invoices.fecha_emision')
            ->orderBy('invoices.id')
            ->get();

        $tituloPeriodo = $this->tituloPeriodo($periodo, $desde, $hasta);

        return view('reports.index', compact(
            'tipo', 'periodo', 'fecha', 'seleccion', 'categoriaId', 'productoIds',
            'desde', 'hasta', 'siguiente', 'anterior',
            'productos', 'categorias', 'companyId',
            'totales', 'porProducto', 'porFecha', 'documentos', 'tituloPeriodo'
        ));
    }

    public function export(Request $request)
    {
        $companyId = Company::getMainCompany()->id;

        $tipo = $request->get('tipo', 'venta');
        $periodo = $request->get('periodo', 'diario');
        $fecha = $request->get('fecha') ? Carbon::parse($request->get('fecha')) : Carbon::today();
        $seleccion = $request->get('seleccion', 'todos');
        $categoriaId = $request->get('categoria_id');
        $productoIds = (array) $request->get('productos', []);

        [$desde, $hasta] = $this->resolvePeriodo($periodo, $fecha);

        $porProducto = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('invoices.company_id', $companyId)
            ->where('invoices.sunat_estado', '!=', 'ANULADO')
            ->whereBetween('invoices.fecha_emision', [$desde, $hasta])
            ->when($tipo !== 'compra', fn ($q) => $q->where('invoices.tipo_documento', '!=', 'CO'))
            ->when($tipo === 'compra', fn ($q) => $q->where('invoices.tipo_documento', 'CO'))
            ->when($seleccion === 'categoria' && $categoriaId, fn ($q) => $q->where('products.category_id', $categoriaId))
            ->when($seleccion === 'productos' && count($productoIds) > 0, fn ($q) => $q->whereIn('invoice_items.product_id', $productoIds))
            ->selectRaw(
                'products.codigo,
                 products.descripcion,
                 categories.nombre as categoria,
                 SUM(invoice_items.cantidad) as cantidad,
                 SUM(invoice_items.precio_venta) as importe,
                 COUNT(DISTINCT invoice_items.invoice_id) as documentos'
            )
            ->groupBy('products.codigo', 'products.descripcion', 'categories.nombre')
            ->orderByRaw('importe DESC')
            ->get();

        $data = [[
            'Código', 'Producto', 'Categoría',
            'N° Documentos', ucfirst($tipo == 'compra' ? 'Cantidad Comprada' : 'Cantidad Vendida'),
            'Importe',
        ]];

        foreach ($porProducto as $p) {
            $data[] = [
                $p->codigo,
                $p->descripcion,
                $p->categoria ?? 'Sin categoría',
                $p->documentos,
                $p->cantidad,
                round((float) $p->importe, 2),
            ];
        }

        return $this->exportSpreadsheet($data, 'reporte_' . $tipo . '_' . $periodo . '_' . $desde . '.xlsx');
    }

    private function resolvePeriodo(string $periodo, Carbon $fecha): array
    {
        switch ($periodo) {
            case 'semanal':
                $desde = $fecha->copy()->startOfWeek()->format('Y-m-d');
                $hasta = $fecha->copy()->endOfWeek()->format('Y-m-d');
                $siguiente = $fecha->copy()->addWeek()->format('Y-m-d');
                $anterior = $fecha->copy()->subWeek()->format('Y-m-d');
                break;
            case 'mensual':
                $desde = $fecha->copy()->startOfMonth()->format('Y-m-d');
                $hasta = $fecha->copy()->endOfMonth()->format('Y-m-d');
                $siguiente = $fecha->copy()->addMonth()->format('Y-m-d');
                $anterior = $fecha->copy()->subMonth()->format('Y-m-d');
                break;
            default:
                $desde = $hasta = $fecha->format('Y-m-d');
                $siguiente = $fecha->copy()->addDay()->format('Y-m-d');
                $anterior = $fecha->copy()->subDay()->format('Y-m-d');
                break;
        }

        return [$desde, $hasta, $siguiente, $anterior];
    }

    private function tituloPeriodo(string $periodo, string $desde, string $hasta): string
    {
        $fmt = fn ($d) => Carbon::parse($d)->format('d/m/Y');
        return match ($periodo) {
            'semanal' => "Semana: {$fmt($desde)} - {$fmt($hasta)}",
            'mensual' => "Mes: " . Carbon::parse($desde)->isoFormat('MMMM YYYY'),
            default => "Día: {$fmt($desde)}",
        };
    }

    private function exportSpreadsheet(array $data, string $filename)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data, null, 'A1');

        $numCols = count($data[0] ?? []);
        if ($numCols > 0) {
            $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($numCols);
            foreach (range('A', $lastCol) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $tempFile = tempnam(sys_get_temp_dir(), 'export');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}