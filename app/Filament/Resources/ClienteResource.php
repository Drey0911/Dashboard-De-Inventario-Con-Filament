<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Cliente;
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
use App\Exports\ClientesExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Clientes'; 
    
    protected static ?string $modelLabel = 'Cliente'; 
    
    protected static ?string $pluralModelLabel = 'Clientes'; 
    
    protected static ?string $navigationGroup = 'Ventas';
    
    protected static ?string $slug = 'clientes'; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nombre')
                    ->required()
                    ->maxLength(255)
                    ->label('Nombre del Cliente') 
                    ->columnSpanFull(),

                TextInput::make('contacto')
                    ->maxLength(255)
                    ->label('Persona de Contacto'),

                TextInput::make('telefono')
                    ->tel()
                    ->maxLength(20)
                    ->label('Teléfono'),

                TextInput::make('DNI')
                    ->maxLength(20)
                    ->unique(ignoreRecord: true)
                    ->label('DNI/Cédula'),

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

                TextColumn::make('DNI')
                    ->searchable()
                    ->label('DNI')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('email')
                    ->searchable()
                    ->label('Email')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('ciudad')
                    ->searchable()
                    ->label('Ciudad')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    ->options(fn () => Cliente::query()->pluck('ciudad', 'ciudad')->toArray())
                    ->searchable()
                    ->label('Ciudad'),
                SelectFilter::make('pais')
                    ->options(fn () => Cliente::query()->pluck('pais', 'pais')->toArray())
                    ->searchable()
                    ->label('País'),
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
                                new ClientesExport($records),
                                'clientes-seleccionados.xlsx'
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
                        return Excel::download(new ClientesExport, 'clientes.xlsx');
                    }),
                Action::make('exportAllPDF')
                    ->label('Exportar A PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function () {
                        $clientes = Cliente::all();
                        $pdf = Pdf::loadView('clientes.pdf', compact('clientes'));
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->stream();
                        }, "clientes.pdf");
                    }),
            ])
            ->emptyStateHeading('No hay clientes registrados')
            ->emptyStateDescription('Registra tu primer cliente para comenzar.')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\ClienteResource\Pages\ListClientes::route('/'),
            'create' => \App\Filament\Resources\ClienteResource\Pages\CreateCliente::route('/create'),
            'edit' => \App\Filament\Resources\ClienteResource\Pages\EditCliente::route('/{record}/edit'),
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