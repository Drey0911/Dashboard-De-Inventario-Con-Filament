<?php

namespace App\Exports;

use App\Models\Categoria;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoriasExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $records;

    public function __construct($records = null)
    {
        $this->records = $records;
    }

    public function collection()
    {
        return $this->records ?: Categoria::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Descripción',
            'Estado',
            'Creado',
            'Actualizado'
        ];
    }

    public function map($categoria): array
    {
        return [
            $categoria->id,
            $categoria->nombre,
            $categoria->descripcion ?? 'N/A',
            $categoria->activo ? 'Activo' : 'Inactivo',
            $categoria->created_at->format('d/m/Y H:i'),
            $categoria->updated_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A:F' => ['alignment' => ['vertical' => 'center']],
        ];
    }
}