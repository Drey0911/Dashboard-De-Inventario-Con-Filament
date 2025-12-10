<?php

namespace App\Filament\Resources\SalidaResource\Pages;

use App\Filament\Resources\SalidaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Producto;
use Filament\Notifications\Notification;

class EditSalida extends EditRecord
{
    protected static string $resource = SalidaResource::class;

    protected function beforeSave(): void
    {
        // Restaurar stock anterior antes de actualizar
        $salida = $this->record;
        $salida->producto->increment('stock', $salida->cantidad);
        
        // Verificar nuevo stock
        $nuevaCantidad = $this->data['cantidad'];
        $producto = Producto::find($this->data['producto_id']);
        
        if ($producto->stock < $nuevaCantidad) {
            // Si no hay suficiente, revertir
            $salida->producto->decrement('stock', $salida->cantidad);
            
            Notification::make()
                ->title('Error de Stock')
                ->body('Stock insuficiente. Disponible: ' . $producto->stock)
                ->danger()
                ->persistent()
                ->send();
            
            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        // Aplicar nuevo stock
        $salida = $this->record;
        $salida->producto->decrement('stock', $salida->cantidad);
        
        Notification::make()
            ->title('Salida actualizada')
            ->body('Stock actualizado correctamente')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    // Restaurar stock antes de eliminar
                    $salida = $this->record;
                    $salida->producto->increment('stock', $salida->cantidad);
                    
                    Notification::make()
                        ->title('Stock restaurado')
                        ->body('Se restauraron ' . $salida->cantidad . ' unidades al stock')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}