<?php

namespace App\Exports;

use App\Models\Proveedor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProveedoresExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return Proveedor::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Contacto',
            'Teléfono',
            'Email',
            'Dirección',
            'Ciudad',
            'País',
            'Estado',
            'Creado'
        ];
    }

    public function map($proveedor): array
    {
        return [
            $proveedor->id,
            $proveedor->nombre,
            $proveedor->contacto ?? 'N/A',
            $proveedor->telefono ?? 'N/A',
            $proveedor->email ?? 'N/A',
            $proveedor->direccion ?? 'N/A',
            $proveedor->ciudad ?? 'N/A',
            $proveedor->pais ?? 'N/A',
            $proveedor->activo ? 'Activo' : 'Inactivo',
            $proveedor->created_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A:K' => ['alignment' => ['vertical' => 'center']],
        ];
    }
}