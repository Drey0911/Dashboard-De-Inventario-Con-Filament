<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Salida;
use App\Models\Producto;
use App\Models\Cliente;
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
use App\Exports\SalidasExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Actions\ActionGroup;
use Filament\Notifications\Notification;

class SalidaResource extends Resource
{
    protected static ?string $model = Salida::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationLabel = 'Salidas'; 
    protected static ?string $modelLabel = 'Salida'; 
    protected static ?string $pluralModelLabel = 'Salidas'; 
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?string $slug = 'salidas'; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de la Salida')
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
                            
                        Select::make('cliente_id')
                            ->relationship('cliente', 'nombre')
                            ->searchable()
                            ->preload()
                            ->label('Cliente'),
                            
                        Select::make('tipo_salida')
                            ->options([
                                'venta' => 'Venta',
                                'devolucion' => 'Devolución',
                                'muestra' => 'Muestra',
                                'perdida' => 'Pérdida',
                                'daño' => 'Daño',
                                'ajuste' => 'Ajuste',
                                'otro' => 'Otro',
                            ])
                            ->default('venta')
                            ->required()
                            ->label('Tipo de Salida'),
                            
                        DatePicker::make('fecha_salida')
                            ->required()
                            ->default(now())
                            ->label('Fecha de Salida'),
                            
                        TextInput::make('numero_factura')
                            ->maxLength(100)
                            ->label('Número de Factura'),
                    ])
                    ->columns(2),

                Section::make('Detalles de la Salida')
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
                            ->label('Total de la Salida')
                            ->extraAttributes(['class' => 'font-bold text-lg']),
                            
                        Hidden::make('precio_total')
                            ->default(0)
                            ->afterStateHydrated(function (callable $set, $state) {
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

                TextColumn::make('fecha_salida')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Fecha')
                    ->searchable(),

                TextColumn::make('producto.nombre')
                    ->sortable()
                    ->searchable()
                    ->label('Producto'),

                TextColumn::make('cliente.nombre')
                    ->sortable()
                    ->searchable()
                    ->label('Cliente')
                    ->placeholder('N/A'),

                TextColumn::make('tipo_salida')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'venta' => 'success',
                        'devolucion' => 'warning',
                        'muestra' => 'info',
                        'perdida' => 'danger',
                        'daño' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'venta' => 'Venta',
                        'devolucion' => 'Devolución',
                        'muestra' => 'Muestra',
                        'perdida' => 'Pérdida',
                        'daño' => 'Daño',
                        'ajuste' => 'Ajuste',
                        default => ucfirst($state),
                    })
                    ->sortable()
                    ->label('Tipo'),

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

                SelectFilter::make('cliente_id')
                    ->relationship('cliente', 'nombre')
                    ->searchable()
                    ->preload()
                    ->label('Cliente'),

                SelectFilter::make('tipo_salida')
                    ->options([
                        'venta' => 'Venta',
                        'devolucion' => 'Devolución',
                        'muestra' => 'Muestra',
                        'perdida' => 'Pérdida',
                        'daño' => 'Daño',
                        'ajuste' => 'Ajuste',
                        'otro' => 'Otro',
                    ])
                    ->label('Tipo de Salida'),

                Filter::make('fecha_salida')
                    ->form([
                        DatePicker::make('desde'),
                        DatePicker::make('hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_salida', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_salida', '<=', $date),
                            );
                    })
                    ->label('Rango de Fechas'),

                Filter::make('mes_actual')
                    ->label('Este mes')
                    ->query(fn (Builder $query): Builder => $query->thisMonth()),

                Filter::make('mes_anterior')
                    ->label('Mes anterior')
                    ->query(fn (Builder $query): Builder => $query->lastMonth()),

                Filter::make('ventas')
                    ->label('Solo Ventas')
                    ->query(fn (Builder $query): Builder => $query->ventas()),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->mutateRecordDataUsing(function (array $data): array {
                            // Mostrar stock actual en edición (incluyendo esta salida)
                            if (isset($data['producto_id'])) {
                                $producto = Producto::find($data['producto_id']);
                                if ($producto) {
                                    $data['stock_actual'] = $producto->stock + $data['cantidad']; // Stock actual + cantidad de esta salida
                                }
                            }
                            return $data;
                        })
                        ->before(function (Salida $record) {
                            // Restaurar stock temporalmente para edición
                            $record->producto->increment('stock', $record->cantidad);
                        })
                        ->after(function (Salida $record) {
                            // Descontar stock después de editar
                            if ($record->producto->stock >= $record->cantidad) {
                                $record->producto->decrement('stock', $record->cantidad);
                            } else {
                                throw new \Exception('No hay suficiente stock para esta salida');
                            }
                            
                            Notification::make()
                                ->title('Salida actualizada')
                                ->body('Stock del producto actualizado correctamente')
                                ->success()
                                ->send();
                        }),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->before(function (Salida $record) {
                            // Restaurar stock antes de eliminar
                            $record->producto->increment('stock', $record->cantidad);
                            
                            Notification::make()
                                ->title('Salida eliminada')
                                ->body('Stock del producto restaurado correctamente')
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
                                $record->producto->increment('stock', $record->cantidad);
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
                        return Excel::download(new SalidasExport, 'salidas.xlsx');
                    }),
                Action::make('exportAllPDF')
                    ->label('Exportar a PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function () {
                        $salidas = Salida::with(['producto', 'cliente', 'usuario'])->get();
                        $totalValor = $salidas->sum('precio_total');
                        $totalCantidad = $salidas->sum('cantidad');
                        $pdf = Pdf::loadView('salidas.pdf', compact('salidas', 'totalValor', 'totalCantidad'));
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->stream();
                        }, "salidas.pdf");
                    }),
            ])
            ->emptyStateHeading('No hay salidas registradas')
            ->emptyStateDescription('Registra tu primera salida de inventario.')
            ->emptyStateIcon('heroicon-o-arrow-up-tray')
            ->defaultSort('fecha_salida', 'desc')
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\SalidaResource\Pages\ListSalidas::route('/'),
            'create' => \App\Filament\Resources\SalidaResource\Pages\CreateSalida::route('/create'),
            'edit' => \App\Filament\Resources\SalidaResource\Pages\EditSalida::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('fecha_salida', today())->count();
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        $hoy = static::getModel()::whereDate('fecha_salida', today())->count();
        return $hoy > 0 ? 'success' : 'primary';
    }
}