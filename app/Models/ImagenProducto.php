<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ImagenProducto extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'ruta_local',
        'url_original',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
