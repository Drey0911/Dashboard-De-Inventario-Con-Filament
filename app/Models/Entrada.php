<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Entrada extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'entradas';

    protected $fillable = [
        'producto_id',
        'proveedor_id',
        'cantidad',
        'precio_unitario',
        'precio_total',
        'fecha_entrada',
        'numero_factura',
        'descripcion',
        'user_id',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'precio_total' => 'decimal:2',
        'fecha_entrada' => 'date',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('fecha_entrada', now()->month)
                     ->whereYear('fecha_entrada', now()->year);
    }

    public function scopeLastMonth($query)
    {
        return $query->whereMonth('fecha_entrada', now()->subMonth()->month)
                     ->whereYear('fecha_entrada', now()->subMonth()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('fecha_entrada', now()->year);
    }
}