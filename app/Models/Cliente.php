<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;   
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'contacto',
        'telefono',
        'DNI',
        'email',
        'direccion',
        'ciudad',
        'pais',
        'notas',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}