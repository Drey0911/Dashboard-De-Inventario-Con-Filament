<?php

namespace App\Filament\Resources\EntradaResource\Pages;

use App\Filament\Resources\EntradaResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Producto;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreateEntrada extends CreateRecord
{
    protected static string $resource = EntradaResource::class;

    protected static ?string $title = 'Crear Entrada';
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['precio_total'] = ($data['cantidad'] ?? 0) * ($data['precio_unitario'] ?? 0);
        
        $data['user_id'] = Auth::check() ? Auth::user()->id : 1;
        
        $data['activo'] = true;
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        // Obtener la entrada recién creada
        $entrada = $this->record;
        
        // Actualizar stock del producto
        if ($entrada->producto) {
            $producto = Producto::find($entrada->producto_id);
            if ($producto) {
                $producto->increment('stock', $entrada->cantidad);
                
                // Enviar notificación
                Notification::make()
                    ->title('Entrada creada exitosamente')
                    ->body("Se agregaron {$entrada->cantidad} unidades de {$producto->nombre}. Stock actual: {$producto->stock}")
                    ->success()
                    ->send();
            }
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}