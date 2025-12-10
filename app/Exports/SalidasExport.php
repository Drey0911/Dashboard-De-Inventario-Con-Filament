<?php

namespace App\Exports;

use App\Models\Salida;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalidasExport implements FromCollection, WithHeadings, WithMapping
{
    protected $salidas;

    public function __construct($salidas = null)
    {
        $this->salidas = $salidas ?? Salida::with(['producto', 'cliente', 'usuario'])->get();
    }

    public function collection()
    {
        return $this->salidas;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha',
            'Producto',
            'Cliente',
            'Tipo',
            'Cantidad',
            'Precio Unitario',
            'Total',
            'Factura',
            'Descripción',
            'Registrado por',
            'Fecha Registro'
        ];
    }

    public function map($salida): array
    {
        return [
            $salida->id,
            $salida->fecha_salida->format('d/m/Y'),
            $salida->producto->nombre ?? 'N/A',
            $salida->cliente->nombre ?? 'N/A',
            $salida->tipo_salida,
            $salida->cantidad,
            $salida->precio_unitario,
            $salida->precio_total,
            $salida->numero_factura ?? 'N/A',
            $salida->descripcion ?? '',
            $salida->usuario->name ?? 'N/A',
            $salida->created_at->format('d/m/Y H:i'),
        ];
    }
}