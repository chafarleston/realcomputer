<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    use HasFactory;

    protected $table = 'cashregisters';

    protected $fillable = [
        'company_id', 'user_id', 'monto_apertura', 'monto_cierre',
        'ventas_efectivo', 'ventas_tarjeta', 'ventas_yape', 'ventas_plin', 'ventas_otro',
        'cantidad_ventas', 'total_ventas', 'estado', 'fecha_apertura', 'fecha_cierre', 'observaciones', 'referencia',
        'compras_efectivo', 'compras_tarjeta', 'compras_yape', 'compras_plin', 'compras_otro',
        'cantidad_compras', 'total_compras', 'ingresos_total', 'gastos_total', 'saldo_final',
    ];

    protected $casts = [
        'monto_apertura' => 'decimal:2',
        'monto_cierre' => 'decimal:2',
        'ventas_efectivo' => 'decimal:2',
        'ventas_tarjeta' => 'decimal:2',
        'ventas_yape' => 'decimal:2',
        'ventas_plin' => 'decimal:2',
        'ventas_otro' => 'decimal:2',
        'total_ventas' => 'decimal:2',
        'compras_efectivo' => 'decimal:2',
        'compras_tarjeta' => 'decimal:2',
        'compras_yape' => 'decimal:2',
        'compras_plin' => 'decimal:2',
        'compras_otro' => 'decimal:2',
        'total_compras' => 'decimal:2',
        'ingresos_total' => 'decimal:2',
        'gastos_total' => 'decimal:2',
        'saldo_final' => 'decimal:2',
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movements()
    {
        return $this->hasMany(CashMovement::class);
    }

    public function isOpen()
    {
        return $this->estado === 'ABIERTA';
    }

    public function getVentasFacturas()
    {
        return $this->hasMany(Invoice::class)->where('tipo_documento', '01');
    }

    public function getVentasBoletas()
    {
        return $this->hasMany(Invoice::class)->where('tipo_documento', '03');
    }

    public function getVentasNV()
    {
        return $this->hasMany(Invoice::class)->where('tipo_documento', 'NV');
    }
}