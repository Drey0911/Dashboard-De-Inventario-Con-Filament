<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('precio_total', 10, 2);
            $table->date('fecha_salida');
            $table->string('numero_factura')->nullable();
            $table->string('tipo_salida')->default('venta'); // venta, devolución, muestra, perdida, etc.
            $table->text('descripcion')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('activo')->default(true);
            $table->softDeletes();
            $table->timestamps();
            
            // Índices
            $table->index('fecha_salida');
            $table->index('tipo_salida');
            $table->index('numero_factura');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salidas');
    }
};