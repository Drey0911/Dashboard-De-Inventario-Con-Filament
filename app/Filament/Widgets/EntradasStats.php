<?php

namespace App\Filament\Widgets;

use App\Models\Entrada;
use App\Models\Producto;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EntradasStats extends BaseWidget
{
    protected static ?int $sort = 1;
    protected ?string $heading = 'Entradas Y Salidas';
    
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $hoy = now()->toDateString();
        
        // Estadísticas del día
        $entradasHoy = Entrada::whereDate('fecha_entrada', $hoy)->count();
        $cantidadHoy = Entrada::whereDate('fecha_entrada', $hoy)->sum('cantidad');
        $valorHoy = Entrada::whereDate('fecha_entrada', $hoy)->sum('precio_total') ?? 0;
        
        // Estadísticas del mes
        $mesActual = now()->month;
        $anoActual = now()->year;
        
        $entradasMes = Entrada::whereMonth('fecha_entrada', $mesActual)
            ->whereYear('fecha_entrada', $anoActual)
            ->count();
            
        $cantidadMes = Entrada::whereMonth('fecha_entrada', $mesActual)
            ->whereYear('fecha_entrada', $anoActual)
            ->sum('cantidad') ?? 0;
            
        $valorMes = Entrada::whereMonth('fecha_entrada', $mesActual)
            ->whereYear('fecha_entrada', $anoActual)
            ->sum('precio_total') ?? 0;
            
        // Comparación con mes anterior
        $mesAnterior = now()->subMonth()->month;
        $anoAnterior = now()->subMonth()->year;
        
        $entradasMesAnterior = Entrada::whereMonth('fecha_entrada', $mesAnterior)
            ->whereYear('fecha_entrada', $anoAnterior)
            ->count();
            
        $diferencia = $entradasMesAnterior > 0 
            ? (($entradasMes - $entradasMesAnterior) / $entradasMesAnterior) * 100 
            : 0;

        return [
            Stat::make('Entradas Hoy', $entradasHoy)
                ->description($cantidadHoy . ' unidades')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('primary')
                ->chart($this->getEntradasUltimos7Dias()),

            Stat::make('Entradas del Mes', $entradasMes)
                ->description(number_format($diferencia, 1) . '% vs mes anterior')
                ->descriptionIcon($diferencia >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($diferencia >= 0 ? 'success' : 'danger')
                ->chart($this->getEntradasUltimos6Meses()),

            Stat::make('Valor del Mes', '$' . number_format($valorMes, 2))
                ->description($cantidadMes . ' unidades totales')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('warning')
                ->chart($this->getValorUltimos6Meses()),
        ];
    }

    protected function getEntradasUltimos7Dias(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = now()->subDays($i)->toDateString();
            $data[] = Entrada::whereDate('fecha_entrada', $fecha)->count();
        }
        return $data;
    }

    protected function getEntradasUltimos6Meses(): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = now()->subMonths($i);
            $data[] = Entrada::whereMonth('fecha_entrada', $mes->month)
                ->whereYear('fecha_entrada', $mes->year)
                ->count();
        }
        return $data;
    }

    protected function getValorUltimos6Meses(): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = now()->subMonths($i);
            $data[] = Entrada::whereMonth('fecha_entrada', $mes->month)
                ->whereYear('fecha_entrada', $mes->year)
                ->sum('precio_total') ?? 0;
        }
        return $data;
    }

    public static function canView(): bool
    {
        return Auth::check();
    }
}
