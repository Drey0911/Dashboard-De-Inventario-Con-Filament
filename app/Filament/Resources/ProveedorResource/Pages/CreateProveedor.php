<?php

namespace App\Filament\Resources\ProveedorResource\Pages;

use App\Filament\Resources\ProveedorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProveedor extends CreateRecord
{
    protected static string $resource = ProveedorResource::class;
    
    protected static ?string $title = 'Crear Proveedor';
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index'); 
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Proveedor creado exitosamente';
    }
    
    protected function getCreateFormActionLabel(): string
    {
        return 'Crear';
    }
    
    protected function getCancelFormActionLabel(): string
    {
        return 'Cancelar';
    }
}