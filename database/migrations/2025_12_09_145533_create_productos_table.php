<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 10, 2);
            $table->integer('stock')->default(0);
            $table->foreignId('categoria_id')
                ->nullable()
                ->constrained('categorias')
                ->onDelete('set null');
            $table->foreignId('proveedor_id')
                ->nullable()
                ->constrained('proveedores')
                ->onDelete('set null');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('nombre');
            $table->index('precio');
            $table->index('stock');
            $table->index('activo');
            $table->index('categoria_id');
            $table->index('proveedor_id');
            $table->index('created_at');
            $table->index('deleted_at');
            
            // Índice compuesto para búsquedas frecuentes
            $table->index(['activo', 'stock']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};