<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashMovementController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('permission', 'view_cashregisters');

        $companyId = Company::getMainCompany()->id;
        $cajaAbierta = CashRegister::where('company_id', $companyId)
            ->where('estado', 'ABIERTA')
            ->first();

        if ($cajaAbierta) {
            $movimientos = CashMovement::where('cash_register_id', $cajaAbierta->id)
                ->with('user')
                ->orderBy('fecha', 'desc')
                ->paginate(25);

            $totalIngresos = round((float) CashMovement::where('cash_register_id', $cajaAbierta->id)
                ->where('tipo', 'INGRESO')->sum('monto'), 2);
            $totalGastos = round((float) CashMovement::where('cash_register_id', $cajaAbierta->id)
                ->where('tipo', 'GASTO')->sum('monto'), 2);
        } else {
            $movimientos = collect();
            $totalIngresos = 0;
            $totalGastos = 0;
        }

        return view('cash_movements.index', compact('movimientos', 'cajaAbierta', 'companyId', 'totalIngresos', 'totalGastos'));
    }

    public function store(Request $request)
    {
        $this->authorize('permission', 'view_cashregisters');

        $request->validate([
            'tipo' => 'required|in:INGRESO,GASTO',
            'monto' => 'required|numeric|min:0.01',
            'concepto' => 'nullable|string|max:255',
            'fecha' => 'nullable|date',
        ]);

        $companyId = Company::getMainCompany()->id;
        $cajaAbierta = CashRegister::where('company_id', $companyId)
            ->where('estado', 'ABIERTA')
            ->first();

        if (!$cajaAbierta) {
            return back()->with('error', 'No hay caja abierta. Abra la caja antes de registrar ingresos o gastos.');
        }

        $movimiento = CashMovement::create([
            'company_id' => $companyId,
            'cash_register_id' => $cajaAbierta->id,
            'user_id' => Auth::id(),
            'tipo' => $request->tipo,
            'monto' => $request->monto,
            'concepto' => $request->concepto,
            'fecha' => $request->fecha ?: now(),
        ]);

        $this->recalculateMovementsTotals($cajaAbierta);

        try {
            app(\App\Services\PrintService::class)->printCashMovement($movimiento);
        } catch (\Exception $e) {
            \Log::error('Print cash movement error: ' . $e->getMessage());
        }

        return back()->with('success', ($request->tipo === 'INGRESO' ? 'Ingreso' : 'Gasto') . ' registrado correctamente');
    }

    public function destroy(CashMovement $cashMovement)
    {
        $this->authorize('permission', 'view_cashregisters');

        $cashRegister = $cashMovement->cashRegister;
        $cashMovement->delete();

        if ($cashRegister && $cashRegister->estado === 'ABIERTA') {
            $this->recalculateMovementsTotals($cashRegister);
        }

        return back()->with('success', 'Movimiento eliminado');
    }

    private function recalculateMovementsTotals(CashRegister $caja): void
    {
        $ingresos = (float) $caja->movements()->where('tipo', 'INGRESO')->sum('monto');
        $gastos = (float) $caja->movements()->where('tipo', 'GASTO')->sum('monto');

        $caja->update([
            'ingresos_total' => $ingresos,
            'gastos_total' => $gastos,
        ]);
    }
}
