<?php

namespace App\Filament\Widgets;

use App\Models\Salida;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class SalidasStats extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $hoy = now()->toDateString();
        
        // Estadísticas del día
        $salidasHoy = Salida::whereDate('fecha_salida', $hoy)->count();
        $cantidadHoy = Salida::whereDate('fecha_salida', $hoy)->sum('cantidad');
        $valorHoy = Salida::whereDate('fecha_salida', $hoy)->sum('precio_total') ?? 0;
        
        // Ventas del día
        $ventasHoy = Salida::whereDate('fecha_salida', $hoy)
            ->where('tipo_salida', 'venta')
            ->count();
        
        // Estadísticas del mes
        $mesActual = now()->month;
        $anoActual = now()->year;
        
        $salidasMes = Salida::whereMonth('fecha_salida', $mesActual)
            ->whereYear('fecha_salida', $anoActual)
            ->count();
            
        $ventasMes = Salida::whereMonth('fecha_salida', $mesActual)
            ->whereYear('fecha_salida', $anoActual)
            ->where('tipo_salida', 'venta')
            ->count();
            
        $cantidadMes = Salida::whereMonth('fecha_salida', $mesActual)
            ->whereYear('fecha_salida', $anoActual)
            ->sum('cantidad') ?? 0;
            
        $valorMes = Salida::whereMonth('fecha_salida', $mesActual)
            ->whereYear('fecha_salida', $anoActual)
            ->sum('precio_total') ?? 0;
            
        $valorVentasMes = Salida::whereMonth('fecha_salida', $mesActual)
            ->whereYear('fecha_salida', $anoActual)
            ->where('tipo_salida', 'venta')
            ->sum('precio_total') ?? 0;
            
        // Comparación con mes anterior
        $mesAnterior = now()->subMonth()->month;
        $anoAnterior = now()->subMonth()->year;
        
        $salidasMesAnterior = Salida::whereMonth('fecha_salida', $mesAnterior)
            ->whereYear('fecha_salida', $anoAnterior)
            ->count();
            
        $diferencia = $salidasMesAnterior > 0 
            ? (($salidasMes - $salidasMesAnterior) / $salidasMesAnterior) * 100 
            : ($salidasMes > 0 ? 100 : 0);

        return [
            Stat::make('Salidas Hoy', $salidasHoy)
                ->description($ventasHoy . ' ventas')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('primary')
                ->chart($this->getSalidasUltimos7Dias()),

            Stat::make('Salidas del Mes', $salidasMes)
                ->description(number_format($diferencia, 1) . '% vs mes anterior')
                ->descriptionIcon($diferencia >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($diferencia >= 0 ? 'success' : 'danger')
                ->chart($this->getSalidasUltimos6Meses()),

            Stat::make('Ventas del Mes', '$' . number_format($valorVentasMes, 2))
                ->description($ventasMes . ' transacciones')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('warning')
                ->chart($this->getVentasUltimos6Meses()),
        ];
    }

    protected function getSalidasUltimos7Dias(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = now()->subDays($i)->toDateString();
            $data[] = Salida::whereDate('fecha_salida', $fecha)->count();
        }
        return $data;
    }

    protected function getSalidasUltimos6Meses(): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = now()->subMonths($i);
            $data[] = Salida::whereMonth('fecha_salida', $mes->month)
                ->whereYear('fecha_salida', $mes->year)
                ->count();
        }
        return $data;
    }

    protected function getVentasUltimos6Meses(): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = now()->subMonths($i);
            $data[] = Salida::whereMonth('fecha_salida', $mes->month)
                ->whereYear('fecha_salida', $mes->year)
                ->where('tipo_salida', 'venta')
                ->sum('precio_total') ?? 0;
        }
        return $data;
    }

    public static function canView(): bool
    {
        return Auth::check();
    }
}