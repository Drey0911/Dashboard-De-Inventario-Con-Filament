<?php

namespace App\Filament\Widgets;

use App\Models\Producto;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class ProductosBajoStock extends TableWidget
{
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return Producto::query()
            ->where('activo', true)
            ->where('stock', '<=', 10)
            ->orderBy('stock');
    }

    protected function getTableColumns(): array
    {
        return [
            \Filament\Tables\Columns\TextColumn::make('nombre')
                ->label('Producto')
                ->searchable()
                ->sortable(),
                
            \Filament\Tables\Columns\TextColumn::make('stock')
                ->label('Stock')
                ->sortable()
                ->color(fn ($record) => $record->stock == 0 ? 'danger' : 'warning'),
                
            \Filament\Tables\Columns\TextColumn::make('precio')
                ->label('Precio')
                ->money('USD')
                ->sortable(),
                
            \Filament\Tables\Columns\TextColumn::make('categoria.nombre')
                ->label('Categoría')
                ->placeholder('Sin categoría'),
                
            \Filament\Tables\Columns\TextColumn::make('proveedor.nombre')
                ->label('Proveedor')
                ->placeholder('Sin proveedor'),
                
            \Filament\Tables\Columns\IconColumn::make('necesita_reabastecimiento')
                ->label('Reabastecer')
                ->boolean()
                ->getStateUsing(fn ($record) => $record->stock <= 5)
                ->trueIcon('heroicon-o-exclamation-triangle')
                ->trueColor('danger')
                ->falseIcon('heroicon-o-check-circle')
                ->falseColor('success'),
        ];
    }

    protected function getTableHeading(): string
    {
        return 'Productos con Bajo Stock (≤ 10 unidades)';
    }

    public static function canView(): bool
    {
        return Auth::check();
    }
}