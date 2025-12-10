<?php

namespace App\Filament\Resources\EntradaResource\Pages;

use App\Filament\Resources\EntradaResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditEntrada extends EditRecord
{
    protected static string $resource = EntradaResource::class;
    
    protected function beforeSave(): void
    {
        // Validar que no se edite si tiene más de 7 días
        if ($this->record->created_at->diffInDays(now()) > 7) {
            Notification::make()
                ->title('Error de edición')
                ->body('No se puede editar una entrada con más de 7 días de antigüedad.')
                ->danger()
                ->persistent()
                ->send();
            
            $this->halt();
        }
    }
    
    protected function afterSave(): void
    {
        // Mostrar notificación con información actualizada
        $entrada = $this->record;
        $producto = $entrada->producto;
        
        $this->notify(
            'success',
            'Entrada actualizada exitosamente',
            "Stock actual: {$producto->stock} unidades"
        );
    }
}