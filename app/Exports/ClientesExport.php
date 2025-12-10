<?php

namespace App\Exports;

use App\Models\Cliente;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $records;

    public function __construct($records = null)
    {
        $this->records = $records;
    }

    public function collection()
    {
        return $this->records ?: Cliente::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Contacto',
            'Teléfono',
            'DNI',
            'Email',
            'Dirección',
            'Ciudad',
            'País',
            'Estado',
            'Creado',
            'Actualizado'
        ];
    }

    public function map($cliente): array
    {
        return [
            $cliente->id,
            $cliente->nombre,
            $cliente->contacto ?? 'N/A',
            $cliente->telefono ?? 'N/A',
            $cliente->DNI ?? 'N/A',
            $cliente->email ?? 'N/A',
            $cliente->direccion ?? 'N/A',
            $cliente->ciudad ?? 'N/A',
            $cliente->pais ?? 'N/A',
            $cliente->activo ? 'Activo' : 'Inactivo',
            $cliente->created_at->format('d/m/Y H:i'),
            $cliente->updated_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A:L' => ['alignment' => ['vertical' => 'center']],
            'A' => ['alignment' => ['horizontal' => 'left']],
            'B' => ['alignment' => ['horizontal' => 'left']],
            'E' => ['alignment' => ['horizontal' => 'left']],
        ];
    }
}