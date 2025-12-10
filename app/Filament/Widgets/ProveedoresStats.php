<?php

namespace App\Filament\Widgets;

use App\Models\Proveedor;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class ProveedoresStats extends BaseWidget
{
    protected static ?int $sort = 6;
    protected ?string $heading = 'Proveedores';
    
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalProveedores = Proveedor::count();
        $proveedoresActivos = Proveedor::where('activo', true)->count();
        $proveedoresInactivos = Proveedor::where('activo', false)->count();

        return [
            Stat::make('Total Proveedores', $totalProveedores)
                ->description('Todos los proveedores registrados')
                ->descriptionIcon('heroicon-o-truck')
                ->color('primary')
                ->chart($this->getProveedoresChartData()),

            Stat::make('Proveedores Activos', $proveedoresActivos)
                ->description(number_format(($proveedoresActivos / max($totalProveedores, 1)) * 100, 1) . '% del total')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart($this->getProveedoresChartData()),

            Stat::make('Proveedores Inactivos', $proveedoresInactivos)
                ->description(number_format(($proveedoresInactivos / max($totalProveedores, 1)) * 100, 1) . '% del total')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger')
                ->chart($this->getProveedoresChartData()),
        ];
    }

    protected function getProveedoresChartData(): array
    {
        return [
            Proveedor::whereDate('created_at', today()->subDays(6))->count(),
            Proveedor::whereDate('created_at', today()->subDays(5))->count(),
            Proveedor::whereDate('created_at', today()->subDays(4))->count(),
            Proveedor::whereDate('created_at', today()->subDays(3))->count(),
            Proveedor::whereDate('created_at', today()->subDays(2))->count(),
            Proveedor::whereDate('created_at', today()->subDays(1))->count(),
            Proveedor::whereDate('created_at', today())->count(),
        ];
    }

    public static function canView(): bool
    {
        return Auth::check();
    }
}
