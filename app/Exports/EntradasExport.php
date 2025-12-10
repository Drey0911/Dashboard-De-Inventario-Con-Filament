<?php

namespace App\Exports;

use App\Models\Entrada;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class EntradasExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting
{
    protected $records;

    public function __construct($records = null)
    {
        $this->records = $records;
    }

    public function collection()
    {
        return $this->records ?: Entrada::with(['producto', 'proveedor', 'usuario'])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha Entrada',
            'Producto',
            'Proveedor',
            'Cantidad',
            'Precio Unitario',
            'Precio Total',
            'N° Factura',
            'Descripción',
            'Registrado por',
            'Fecha Registro'
        ];
    }

    public function map($entrada): array
    {
        return [
            $entrada->id,
            $entrada->fecha_entrada->format('d/m/Y'),
            $entrada->producto ? $entrada->producto->nombre : 'N/A',
            $entrada->proveedor ? $entrada->proveedor->nombre : 'N/A',
            $entrada->cantidad,
            $entrada->precio_unitario,
            $entrada->precio_total,
            $entrada->numero_factura ?? 'N/A',
            $entrada->descripcion ?? 'N/A',
            $entrada->usuario ? $entrada->usuario->name : 'N/A',
            $entrada->created_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A:K' => ['alignment' => ['vertical' => 'center']],
            'F' => ['alignment' => ['horizontal' => 'right']],
            'G' => ['alignment' => ['horizontal' => 'right']],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE, // Precio Unitario
            'G' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE, // Precio Total
        ];
    }
}