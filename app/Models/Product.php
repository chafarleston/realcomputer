<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'codigo', 'codigo_barras', 'descripcion', 'codigo_sunat',
        'umedida_codigo', 'precio', 'precio_minimo', 'tipo_afectacion',
        'igv_percent', 'estado', 'category_id', 'stock', 'print_destination',
        'is_composite', 'precio_compra',
        'precio_venta_n2', 'precio_venta_n3', 'precio_venta_n4',
        'precio_compra_n2', 'precio_compra_n3', 'precio_compra_n4',
    ];

    protected $casts = [
        'stock' => 'decimal:4',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function components()
    {
        return $this->hasMany(ProductComponent::class, 'parent_product_id');
    }

    public function isComposite()
    {
        return $this->is_composite;
    }

    public function scopeSimple($query)
    {
        return $query->where('is_composite', false);
    }

    public function scopeComposite($query)
    {
        return $query->where('is_composite', true);
    }

    public function priceVenta(int $nivel = 1): float
    {
        if ($nivel <= 1) {
            return (float) $this->precio;
        }
        return (float) ($this->{'precio_venta_n' . min($nivel, 4)} ?? 0);
    }

    public function priceCompra(int $nivel = 1): float
    {
        if ($nivel <= 1) {
            return (float) $this->precio_compra;
        }
        return (float) ($this->{'precio_compra_n' . min($nivel, 4)} ?? 0);
    }
}