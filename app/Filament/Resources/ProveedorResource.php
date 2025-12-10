<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Proveedor;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Exports\ProveedoresExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ProveedorResource extends Resource
{
    protected static ?string $model = Proveedor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Proveedores'; 
    
    protected static ?string $modelLabel = 'Proveedor'; 
    
    protected static ?string $pluralModelLabel = 'Proveedores'; 
    
    protected static ?string $navigationGroup = 'Inventario';
    
    protected static ?string $slug = 'proveedores'; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nombre')
                    ->required()
                    ->maxLength(255)
                    ->label('Nombre del Proveedor') 
                    ->columnSpanFull(),

                TextInput::make('contacto')
                    ->maxLength(255)
                    ->label('Persona de Contacto'),

                TextInput::make('telefono')
                    ->tel()
                    ->maxLength(20)
                    ->label('Teléfono'),

                TextInput::make('email')
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->label('Correo Electrónico'),

                TextInput::make('direccion')
                    ->maxLength(500)
                    ->label('Dirección')
                    ->columnSpanFull(),

                TextInput::make('ciudad')
                    ->maxLength(100)
                    ->label('Ciudad'),

                TextInput::make('pais')
                    ->maxLength(100)
                    ->label('País'),

                Textarea::make('notas')
                    ->rows(3)
                    ->label('Notas Adicionales')
                    ->columnSpanFull(),

                Toggle::make('activo')
                    ->default(true)
                    ->required()
                    ->label('¿Activo?')
                    ->onColor('success')
                    ->offColor('danger'),
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
                    ->label('Nombre'),

                TextColumn::make('contacto')
                    ->searchable()
                    ->label('Contacto')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('telefono')
                    ->searchable()
                    ->label('Teléfono')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('email')
                    ->searchable()
                    ->label('Email')
                    ->toggleable(isToggledHiddenByDefault: false),

                ToggleColumn::make('activo')
                    ->sortable()
                    ->label('Estado'),

                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Creado')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('activo')
                    ->options([
                        '1' => 'Activo',
                        '0' => 'Inactivo',
                    ])
                    ->label('Estado'),
                SelectFilter::make('ciudad')
                    ->options(fn () => Proveedor::query()->pluck('ciudad', 'ciudad')->toArray())
                    ->searchable()
                    ->label('Ciudad'),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Ver'),
                EditAction::make()
                    ->label('Editar'),
                DeleteAction::make()
                    ->label('Eliminar'),
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
                                new ProveedoresExport($records),
                                'proveedores-seleccionados.xlsx'
                            );
                        }),
                ]),
            ])
            ->headerActions([
                Action::make('exportAllExcel')
                    ->label('Exportar A Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        return Excel::download(new ProveedoresExport, 'proveedores.xlsx');
                    }),
                Action::make('exportAllPDF')
                    ->label('Exportar A PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function () {
                        $proveedores = Proveedor::all();
                        $pdf = Pdf::loadView('proveedores.pdf', compact('proveedores'));
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->stream();
                        }, "proveedores.pdf");
                    }),
            ])
            ->emptyStateHeading('No hay proveedores registrados')
            ->emptyStateDescription('Crea tu primer proveedor para comenzar.')
            ->emptyStateIcon('heroicon-o-truck');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\ProveedorResource\Pages\ListProveedores::route('/'),
            'create' => \App\Filament\Resources\ProveedorResource\Pages\CreateProveedor::route('/create'),
            'edit' => \App\Filament\Resources\ProveedorResource\Pages\EditProveedor::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    
    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 10 ? 'success' : 'primary';
    }
}