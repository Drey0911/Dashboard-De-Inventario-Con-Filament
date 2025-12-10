<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Salida extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'salidas';

    protected $fillable = [
        'producto_id',
        'cliente_id',
        'cantidad',
        'precio_unitario',
        'precio_total',
        'fecha_salida',
        'numero_factura',
        'tipo_salida',
        'descripcion',
        'user_id',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'precio_total' => 'decimal:2',
        'fecha_salida' => 'date',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('fecha_salida', now()->month)
                     ->whereYear('fecha_salida', now()->year);
    }

    public function scopeLastMonth($query)
    {
        return $query->whereMonth('fecha_salida', now()->subMonth()->month)
                     ->whereYear('fecha_salida', now()->subMonth()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('fecha_salida', now()->year);
    }

    public function scopeVentas($query)
    {
        return $query->where('tipo_salida', 'venta');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('fecha_salida', today());
    }
}