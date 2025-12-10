<?php

namespace App\Filament\Widgets;

use App\Models\Salida;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SalidasPorMesChart extends ChartWidget
{
    protected static ?int $sort = 2;
    
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $salidas = Salida::select(
            DB::raw('MONTH(fecha_salida) as mes'),
            DB::raw('COUNT(*) as total_salidas'),
            DB::raw('SUM(cantidad) as total_cantidad'),
            DB::raw('SUM(precio_total) as total_valor'),
            DB::raw('SUM(CASE WHEN tipo_salida = "venta" THEN precio_total ELSE 0 END) as total_ventas')
        )
        ->whereYear('fecha_salida', now()->year)
        ->groupBy(DB::raw('MONTH(fecha_salida)'))
        ->orderBy('mes')
        ->get();

        $meses = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];

        $labels = [];
        $dataSalidas = [];
        $dataVentas = [];
        $dataCantidad = [];

        foreach ($meses as $numero => $nombre) {
            $labels[] = $nombre;
            
            $salidaMes = $salidas->firstWhere('mes', $numero);
            $dataSalidas[] = $salidaMes ? $salidaMes->total_salidas : 0;
            $dataVentas[] = $salidaMes ? $salidaMes->total_ventas : 0;
            $dataCantidad[] = $salidaMes ? $salidaMes->total_cantidad : 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Salidas',
                    'data' => $dataSalidas,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Ventas ($)',
                    'data' => $dataVentas,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.3)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'yAxisID' => 'y1',
                    'type' => 'line',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Cantidad Unidades',
                    'data' => $dataCantidad,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.3)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'borderWidth' => 2,
                    'yAxisID' => 'y2',
                    'type' => 'bar',
                    'hidden' => false, 
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Cantidad de Salidas',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Valor Ventas ($)',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
                'y2' => [
                    'type' => 'linear',
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Unidades',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                    'offset' => true,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        return Auth::check();
    }
}