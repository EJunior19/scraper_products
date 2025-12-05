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
            $table->foreignId('categoria_id')
                  ->constrained('categorias')
                  ->onDelete('cascade');

            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 12, 2)->nullable();
            $table->string('sku')->nullable();
            $table->string('url_producto')->unique();
            $table->json('extra_json')->nullable(); // por si luego querés guardar datos extra
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
