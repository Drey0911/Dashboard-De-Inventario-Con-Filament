<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Categoria;
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
use App\Exports\CategoriasExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class CategoriaResource extends Resource
{
    protected static ?string $model = Categoria::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Categorías'; 
    
    protected static ?string $modelLabel = 'Categoría'; 
    
    protected static ?string $pluralModelLabel = 'Categorías'; 
    
    protected static ?string $navigationGroup = 'Inventario';
    
    protected static ?string $slug = 'categorias'; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nombre')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->label('Nombre de la Categoría') 
                    ->columnSpanFull(),

                Textarea::make('descripcion')
                    ->rows(3)
                    ->label('Descripción')
                    ->maxLength(500)
                    ->columnSpanFull(),

                Toggle::make('activo')
                    ->default(true)
                    ->required()
                    ->label('¿Activa?')
                    ->onColor('success')
                    ->offColor('danger')
                    ->inline(false),
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

                TextColumn::make('descripcion')
                    ->searchable()
                    ->label('Descripción')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: false),

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

                TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Actualizado')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('activo')
                    ->options([
                        '1' => 'Activo',
                        '0' => 'Inactivo',
                    ])
                    ->label('Estado'),
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
                                new CategoriasExport($records),
                                'categorias-seleccionadas.xlsx'
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
                        return Excel::download(new CategoriasExport, 'categorias.xlsx');
                    }),
                Action::make('exportAllPDF')
                    ->label('Exportar A PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function () {
                        $categorias = Categoria::all();
                        $pdf = Pdf::loadView('categorias.pdf', compact('categorias'));
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->stream();
                        }, "categorias.pdf");
                    }),
            ])
            ->emptyStateHeading('No hay categorías registradas')
            ->emptyStateDescription('Crea tu primera categoría para comenzar.')
            ->emptyStateIcon('heroicon-o-tag');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\CategoriaResource\Pages\ListCategorias::route('/'),
            'create' => \App\Filament\Resources\CategoriaResource\Pages\CreateCategoria::route('/create'),
            'edit' => \App\Filament\Resources\CategoriaResource\Pages\EditCategoria::route('/{record}/edit'),
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