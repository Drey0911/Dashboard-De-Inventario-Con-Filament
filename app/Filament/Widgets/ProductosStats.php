<?php

namespace App\Filament\Widgets;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Proveedor;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductosStats extends BaseWidget
{
    protected static ?int $sort = 4;
    protected ?string $heading = 'Productos';
    
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalProductos = Producto::count();
        $productosActivos = Producto::where('activo', true)->count();
        $productosInactivos = Producto::where('activo', false)->count();
        $stockTotal = Producto::sum('stock');
        
        // CORRECCIÓN: Usar DB::raw para calcular precio * stock
        $valorTotalStock = Producto::select(DB::raw('SUM(precio * stock) as total'))
            ->value('total') ?? 0;
            
        $bajoStock = Producto::where('stock', '<=', 10)->count();
        $sinStock = Producto::where('stock', '=', 0)->count();
        $precioPromedio = Producto::where('activo', true)->avg('precio') ?? 0;
        $stockPromedio = Producto::where('activo', true)->avg('stock') ?? 0;
        $productosSinCategoria = Producto::whereNull('categoria_id')->count();
        $productosSinProveedor = Producto::whereNull('proveedor_id')->count();

        return [
            Stat::make('Total Productos', $totalProductos)
                ->description('Todos los productos registrados')
                ->descriptionIcon('heroicon-o-cube')
                ->color('primary')
                ->chart($this->getProductosChartData()),

            Stat::make('Productos Activos', $productosActivos)
                ->description(number_format(($productosActivos / max($totalProductos, 1)) * 100, 1) . '% del total')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart($this->getActivosChartData()),

            Stat::make('Stock Total', number_format($stockTotal))
                ->description(number_format($stockPromedio, 1) . ' unidades promedio')
                ->descriptionIcon('heroicon-o-archive-box')
                ->color('info')
                ->chart($this->getStockChartData()),

            Stat::make('Valor Stock', '$' . number_format($valorTotalStock, 2))
                ->description('Valor total en inventario')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('warning')
                ->chart($this->getValorStockChartData()),

            Stat::make('Bajo Stock', $bajoStock)
                ->description($sinStock . ' productos sin stock')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->chart($this->getBajoStockChartData()),

            Stat::make('Productos sin Categoría', $productosSinCategoria)
                ->description($productosSinProveedor . ' sin proveedor')
                ->descriptionIcon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->chart($this->getSinCategoriaChartData()),
        ];
    }

    protected function getProductosChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = Producto::whereDate('created_at', $date)->count();
        }
        return $data;
    }

    protected function getActivosChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = Producto::where('activo', true)->whereDate('created_at', $date)->count();
        }
        return $data;
    }

    protected function getStockChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = Producto::whereDate('created_at', $date)->sum('stock');
        }
        return $data;
    }

    protected function getValorStockChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = Producto::whereDate('created_at', $date)
                ->select(DB::raw('COALESCE(SUM(precio * stock), 0) as total'))
                ->value('total');
        }
        return $data;
    }

    protected function getBajoStockChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = Producto::where('stock', '<=', 10)
                ->whereDate('created_at', $date)
                ->count();
        }
        return $data;
    }

    protected function getSinCategoriaChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = Producto::whereNull('categoria_id')
                ->whereDate('created_at', $date)
                ->count();
        }
        return $data;
    }

    public static function canView(): bool
    {
        return Auth::check();
    }
}
