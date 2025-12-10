<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Proveedor;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\IconColumn;
use App\Exports\ProductosExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Columns\Summarizers\Average;

class ProductoResource extends Resource
{
    protected static ?string $model = Producto::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Productos'; 
    
    protected static ?string $modelLabel = 'Producto'; 
    
    protected static ?string $pluralModelLabel = 'Productos'; 
    
    protected static ?string $navigationGroup = 'Inventario';
    
    protected static ?string $slug = 'productos'; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Producto')
                    ->schema([
                        TextInput::make('nombre')
                            ->required()
                            ->maxLength(255)
                            ->label('Nombre del Producto')
                            ->columnSpanFull(),

                        Textarea::make('descripcion')
                            ->rows(3)
                            ->label('Descripción')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Precio y Stock')
                    ->schema([
                        TextInput::make('precio')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0)
                            ->label('Precio Unitario'),

                        TextInput::make('stock')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->label('Stock Disponible'),
                    ])
                    ->columns(2),

                Section::make('Relaciones')
                    ->schema([
                        Select::make('categoria_id')
                            ->relationship('categoria', 'nombre')
                            ->searchable()
                            ->preload()
                            ->label('Categoría')
                            ->nullable(),

                        Select::make('proveedor_id')
                            ->relationship('proveedor', 'nombre')
                            ->searchable()
                            ->preload()
                            ->label('Proveedor')
                            ->nullable(),
                    ])
                    ->columns(2),

                Section::make('Estado')
                    ->schema([
                        Toggle::make('activo')
                            ->default(true)
                            ->required()
                            ->label('¿Producto Activo?')
                            ->onColor('success')
                            ->offColor('danger')
                            ->inline(false),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable()
                    ->label('ID'),

                TextColumn::make('nombre')
                    ->sortable()
                    ->searchable()
                    ->label('Producto')
                    ->limit(30),

                TextColumn::make('descripcion')
                    ->searchable()
                    ->label('Descripción')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('precio')
                    ->sortable()
                    ->money('USD')
                    ->label('Precio')
                    ->summarize([
                        Average::make()->money('USD')->label('Precio Promedio'),
                    ]),

                TextColumn::make('stock')
                    ->sortable()
                    ->label('Stock')
                    ->color(fn ($record) => $record->stock <= 10 ? 'danger' : 'success')
                    ->summarize([
                        Sum::make()->label('Total Stock'),
                    ]),

                TextColumn::make('categoria.nombre')
                    ->sortable()
                    ->searchable()
                    ->label('Categoría')
                    ->placeholder('Sin categoría'),

                TextColumn::make('proveedor.nombre')
                    ->sortable()
                    ->searchable()
                    ->label('Proveedor')
                    ->placeholder('Sin proveedor'),

                ToggleColumn::make('activo')
                    ->sortable()
                    ->label('Estado')
                    ->onColor('success')
                    ->offColor('danger'),

                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Creado')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('categoria_id')
                    ->relationship('categoria', 'nombre')
                    ->searchable()
                    ->preload()
                    ->label('Categoría'),

                SelectFilter::make('proveedor_id')
                    ->relationship('proveedor', 'nombre')
                    ->searchable()
                    ->preload()
                    ->label('Proveedor'),

                TernaryFilter::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),

                Tables\Filters\Filter::make('bajo_stock')
                    ->label('Bajo Stock (≤ 10 unidades)')
                    ->query(fn ($query) => $query->where('stock', '<=', 10)),

                Tables\Filters\Filter::make('sin_stock')
                    ->label('Sin Stock')
                    ->query(fn ($query) => $query->where('stock', '=', 0)),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Ver'),
                EditAction::make()
                    ->label('Editar'),
                DeleteAction::make()
                    ->label('Eliminar')
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                    Tables\Actions\BulkAction::make('exportExcel')
                        ->label('Exportar a Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function ($records) {
                            return Excel::download(
                                new ProductosExport($records),
                                'productos-seleccionados.xlsx'
                            );
                        }),
                    Tables\Actions\BulkAction::make('activar')
                        ->label('Activar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['activo' => true]);
                        }),
                    Tables\Actions\BulkAction::make('desactivar')
                        ->label('Desactivar seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['activo' => false]);
                        }),
                ]),
            ])
            ->headerActions([
                Action::make('exportAllExcel')
                    ->label('Exportar A Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        return Excel::download(new ProductosExport, 'productos.xlsx');
                    }),
                Action::make('exportAllPDF')
                    ->label('Exportar A PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function () {
                        $productos = Producto::with(['categoria', 'proveedor'])->get();
                        $pdf = Pdf::loadView('productos.pdf', compact('productos'));
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->stream();
                        }, "productos.pdf");
                    }),
            ])
            ->emptyStateHeading('No hay productos registrados')
            ->emptyStateDescription('Registra tu primer producto para comenzar.')
            ->emptyStateIcon('heroicon-o-cube')
            ->defaultSort('stock', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\ProductoResource\Pages\ListProductos::route('/'),
            'create' => \App\Filament\Resources\ProductoResource\Pages\CreateProducto::route('/create'),
            'edit' => \App\Filament\Resources\ProductoResource\Pages\EditProducto::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = static::getModel()::count();
        if ($count == 0) return 'danger';
        if ($count > 20) return 'success';
        return 'primary';
    }
}