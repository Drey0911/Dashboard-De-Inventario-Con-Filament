<?php

namespace App\Filament\Resources\SalidaResource\Pages;

use App\Filament\Resources\SalidaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Producto;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreateSalida extends CreateRecord
{
    protected static string $resource = SalidaResource::class;

    protected static ?string $title = 'Crear Salida';
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Salida creado exitosamente';
    }
    
    protected function getCreateFormActionLabel(): string
    {
        return 'Crear';
    }
    
    protected function getCancelFormActionLabel(): string
    {
        return 'Cancelar';
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Validar stock primero
        $producto = Producto::find($data['producto_id']);
        
        if (!$producto) {
            throw new \Exception('Producto no encontrado');
        }
        
        if ($producto->stock < $data['cantidad']) {
            throw new \Exception('Stock insuficiente. Disponible: ' . $producto->stock);
        }
        
        // Calcular precio total
        $data['precio_total'] = $data['cantidad'] * $data['precio_unitario'];
        $data['user_id'] = Auth::id();
        $data['activo'] = true;
        
        // Crear registro
        $record = static::getModel()::create($data);
        
        // Descontar stock
        $producto->decrement('stock', $data['cantidad']);
        
        Notification::make()
            ->title('Salida creada exitosamente')
            ->body('Stock actualizado: ' . ($producto->stock + $data['cantidad']) . ' → ' . $producto->stock . ' unidades')
            ->success()
            ->send();
        
        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}