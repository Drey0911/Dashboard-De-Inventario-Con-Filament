<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Entrada;
use App\Models\Producto;
use App\Models\Proveedor;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use App\Exports\EntradasExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Actions\ActionGroup;
use Filament\Notifications\Notification;

class EntradaResource extends Resource
{
    protected static ?string $model = Entrada::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationLabel = 'Entradas'; 
    protected static ?string $modelLabel = 'Entrada'; 
    protected static ?string $pluralModelLabel = 'Entradas'; 
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $slug = 'entradas'; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de la Entrada')
                    ->schema([
                        Select::make('producto_id')
                            ->relationship('producto', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $producto = Producto::find($state);
                                    if ($producto) {
                                        $set('precio_unitario', $producto->precio);
                                        $set('stock_actual', $producto->stock);
                                    }
                                } else {
                                    $set('stock_actual', 0);
                                }
                            })
                            ->label('Producto'),
                            
                        Placeholder::make('stock_actual')
                            ->content(function (callable $get) {
                                $productoId = $get('producto_id');
                                if ($productoId) {
                                    $producto = Producto::find($productoId);
                                    return $producto ? number_format($producto->stock) : '0';
                                }
                                return '0';
                            })
                            ->label('Stock Actual'),
                            
                        Select::make('proveedor_id')
                            ->relationship('proveedor', 'nombre')
                            ->searchable()
                            ->preload()
                            ->label('Proveedor'),
                            
                        DatePicker::make('fecha_entrada')
                            ->required()
                            ->default(now())
                            ->label('Fecha de Entrada'),
                            
                        TextInput::make('numero_factura')
                            ->maxLength(100)
                            ->label('Número de Factura'),
                    ])
                    ->columns(2),

                Section::make('Detalles de la Entrada')
                    ->schema([
                        TextInput::make('cantidad')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->live(onBlur: true) 
                            ->afterStateUpdated(function ($state, $get, callable $set) {
                                $precio = (float)($get('precio_unitario') ?? 0);
                                $cantidad = (float)$state;
                                $set('precio_total', $cantidad * $precio);
                            })
                            ->label('Cantidad'),
                            
                        TextInput::make('precio_unitario')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $get, callable $set) {
                                $cantidad = (float)($get('cantidad') ?? 0);
                                $precio = (float)$state;
                                $set('precio_total', $cantidad * $precio);
                            })
                            ->label('Precio Unitario'),
                            
                        Placeholder::make('precio_total_placeholder')
                            ->content(function ($get) {
                                $cantidad = (float)($get('cantidad') ?? 0);
                                $precio = (float)($get('precio_unitario') ?? 0);
                                $total = $cantidad * $precio;
                                return '$' . number_format($total, 2);
                            })
                            ->label('Total de la Entrada')
                            ->extraAttributes(['class' => 'font-bold text-lg']),
                            
                        // Hidden field que realmente guarda el valor
                        Hidden::make('precio_total')
                            ->default(0)
                            ->afterStateHydrated(function (callable $set, $state) {
                                // Asegurar que el precio total se inicialice correctamente
                                $set('precio_total', $state ?? 0);
                            }),
                            
                        Textarea::make('descripcion')
                            ->rows(3)
                            ->label('Descripción / Notas')
                            ->columnSpanFull(),
                            
                        Hidden::make('user_id')
                            ->default(fn () => Auth::check() ? Auth::id() : 1),
                            
                        Hidden::make('activo')
                            ->default(true),
                    ])
                    ->columns(2),
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

                TextColumn::make('fecha_entrada')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Fecha')
                    ->searchable(),

                TextColumn::make('producto.nombre')
                    ->sortable()
                    ->searchable()
                    ->label('Producto'),

                TextColumn::make('proveedor.nombre')
                    ->sortable()
                    ->searchable()
                    ->label('Proveedor')
                    ->placeholder('N/A'),

                TextColumn::make('cantidad')
                    ->sortable()
                    ->label('Cantidad')
                    ->summarize([
                        Sum::make()->label('Total'),
                    ]),

                TextColumn::make('precio_unitario')
                    ->money('USD')
                    ->label('Precio Unitario'),

                TextColumn::make('precio_total')
                    ->money('USD')
                    ->label('Total')
                    ->summarize([
                        Sum::make()->money('USD')->label('Valor Total'),
                    ]),

                TextColumn::make('numero_factura')
                    ->searchable()
                    ->label('Factura')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('usuario.name')
                    ->label('Registrado por')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Creado')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('producto_id')
                    ->relationship('producto', 'nombre')
                    ->searchable()
                    ->preload()
                    ->label('Producto'),

                SelectFilter::make('proveedor_id')
                    ->relationship('proveedor', 'nombre')
                    ->searchable()
                    ->preload()
                    ->label('Proveedor'),

                Filter::make('fecha_entrada')
                    ->form([
                        DatePicker::make('desde'),
                        DatePicker::make('hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_entrada', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_entrada', '<=', $date),
                            );
                    })
                    ->label('Rango de Fechas'),

                Filter::make('mes_actual')
                    ->label('Este mes')
                    ->query(fn (Builder $query): Builder => $query->thisMonth()),

                Filter::make('mes_anterior')
                    ->label('Mes anterior')
                    ->query(fn (Builder $query): Builder => $query->lastMonth()),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->mutateRecordDataUsing(function (array $data): array {
                            // Mostrar stock actual en edición
                            if (isset($data['producto_id'])) {
                                $producto = Producto::find($data['producto_id']);
                                if ($producto) {
                                    $data['stock_actual'] = $producto->stock - $data['cantidad']; // Stock antes de esta entrada
                                }
                            }
                            return $data;
                        })
                        ->before(function (Entrada $record) {
                            // Revertir stock temporalmente para edición
                            $record->producto->decrement('stock', $record->cantidad);
                        })
                        ->after(function (Entrada $record) {
                            // Aplicar nuevo stock después de editar
                            $record->producto->increment('stock', $record->cantidad);
                            
                            Notification::make()
                                ->title('Entrada actualizada')
                                ->body('Stock del producto actualizado correctamente')
                                ->success()
                                ->send();
                        }),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->before(function (Entrada $record) {
                            // Revertir stock antes de eliminar
                            $record->producto->decrement('stock', $record->cantidad);
                            
                            Notification::make()
                                ->title('Entrada eliminada')
                                ->body('Stock del producto revertido correctamente')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                $record->producto->decrement('stock', $record->cantidad);
                            }
                        }),
                ]),
            ])
            ->headerActions([
                Action::make('exportAllExcel')
                    ->label('Exportar a Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        return Excel::download(new EntradasExport, 'entradas.xlsx');
                    }),
                Action::make('exportAllPDF')
                    ->label('Exportar a PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function () {
                        $entradas = Entrada::with(['producto', 'proveedor', 'usuario'])->get();
                        $totalValor = $entradas->sum('precio_total');
                        $totalCantidad = $entradas->sum('cantidad');
                        $pdf = Pdf::loadView('entradas.pdf', compact('entradas', 'totalValor', 'totalCantidad'));
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->stream();
                        }, "entradas.pdf");
                    }),
            ])
            ->emptyStateHeading('No hay entradas registradas')
            ->emptyStateDescription('Registra tu primera entrada de inventario.')
            ->emptyStateIcon('heroicon-o-arrow-down-tray')
            ->defaultSort('fecha_entrada', 'desc')
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\EntradaResource\Pages\ListEntradas::route('/'),
            'create' => \App\Filament\Resources\EntradaResource\Pages\CreateEntrada::route('/create'),
            'edit' => \App\Filament\Resources\EntradaResource\Pages\EditEntrada::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('fecha_entrada', today())->count();
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        $hoy = static::getModel()::whereDate('fecha_entrada', today())->count();
        return $hoy > 0 ? 'success' : 'primary';
    }
}