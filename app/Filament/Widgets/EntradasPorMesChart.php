<?php

namespace App\Filament\Widgets;

use App\Models\Entrada;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EntradasPorMesChart extends ChartWidget
{
    protected static ?int $sort = 2;
    
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $entradas = Entrada::select(
            DB::raw('MONTH(fecha_entrada) as mes'),
            DB::raw('COUNT(*) as total_entradas'),
            DB::raw('SUM(cantidad) as total_cantidad'),
            DB::raw('SUM(precio_total) as total_valor')
        )
        ->whereYear('fecha_entrada', now()->year)
        ->groupBy(DB::raw('MONTH(fecha_entrada)'))
        ->orderBy('mes')
        ->get();

        $meses = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];

        $labels = [];
        $dataEntradas = [];
        $dataValor = [];

        foreach ($meses as $numero => $nombre) {
            $labels[] = $nombre;
            
            $entradaMes = $entradas->firstWhere('mes', $numero);
            $dataEntradas[] = $entradaMes ? $entradaMes->total_entradas : 0;
            $dataValor[] = $entradaMes ? $entradaMes->total_valor : 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Cantidad de Entradas',
                    'data' => $dataEntradas,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Valor Total ($)',
                    'data' => $dataValor,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.5)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'borderWidth' => 2,
                    'yAxisID' => 'y1',
                    'type' => 'line',
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Gráfico de barras con línea
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
                        'text' => 'Cantidad de Entradas',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Valor ($)',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
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
