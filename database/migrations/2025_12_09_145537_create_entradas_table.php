<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->onDelete('restrict');
            $table->foreignId('proveedor_id')
                ->nullable()
                ->constrained('proveedores')
                ->onDelete('set null');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('precio_total', 10, 2);
            $table->date('fecha_entrada');
            $table->string('numero_factura', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('fecha_entrada');
            $table->index('numero_factura');
            $table->index('producto_id');
            $table->index('proveedor_id');
            $table->index('user_id');
            $table->index('activo');
            $table->index('created_at');
            $table->index(['fecha_entrada', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entradas');
    }
};