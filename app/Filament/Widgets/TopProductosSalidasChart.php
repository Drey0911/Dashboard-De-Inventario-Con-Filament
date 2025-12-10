<?php

namespace App\Filament\Widgets;

use App\Models\Salida;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TopProductosSalidasChart extends ChartWidget
{
    protected static ?int $sort = 3;
    
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $productos = Salida::select(
            'productos.nombre',
            DB::raw('SUM(salidas.cantidad) as total_cantidad'),
            DB::raw('SUM(salidas.precio_total) as total_valor')
        )
        ->join('productos', 'salidas.producto_id', '=', 'productos.id')
        ->where('salidas.fecha_salida', '>=', now()->subDays(30))
        ->groupBy('productos.id', 'productos.nombre')
        ->orderByDesc('total_cantidad')
        ->limit(10)
        ->get();

        $labels = $productos->pluck('nombre')->map(function ($nombre) {
            return strlen($nombre) > 20 ? substr($nombre, 0, 20) . '...' : $nombre;
        })->toArray();

        $dataCantidad = $productos->pluck('total_cantidad')->toArray();
        $dataValor = $productos->pluck('total_valor')->toArray();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Cantidad Salida',
                    'data' => $dataCantidad,
                    'backgroundColor' => [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#FF6384', '#C9CBCF', '#FF6384', '#36A2EB'
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Valor Total ($)',
                    'data' => $dataValor,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.5)',
                    'borderColor' => 'rgb(75, 192, 192)',
                    'borderWidth' => 2,
                    'type' => 'bar',
                    'yAxisID' => 'y1',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Cambiado a bar para mejor visualización
    }

    protected function getOptions(): array
    {
        // Obtener los datos nuevamente para el tooltip
        $productos = Salida::select(
            'productos.nombre',
            DB::raw('SUM(salidas.cantidad) as total_cantidad'),
            DB::raw('SUM(salidas.precio_total) as total_valor')
        )
        ->join('productos', 'salidas.producto_id', '=', 'productos.id')
        ->where('salidas.fecha_salida', '>=', now()->subDays(30))
        ->groupBy('productos.id', 'productos.nombre')
        ->orderByDesc('total_cantidad')
        ->limit(10)
        ->get();

        $productosArray = $productos->toArray();

        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Cantidad',
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
                    'callbacks' => [
                        'label' => function($context) use ($productosArray) {
                            $index = $context->dataIndex;
                            $producto = $productosArray[$index] ?? null;
                            
                            if ($producto) {
                                if ($context->datasetIndex === 0) { // Cantidad
                                    return 'Cantidad: ' . number_format($producto['total_cantidad']);
                                } else { // Valor
                                    return 'Valor: $' . number_format($producto['total_valor'], 2);
                                }
                            }
                            return $context->dataset->label . ': ' . $context->parsed;
                        }
                    ]
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        return Auth::check();
    }
}
