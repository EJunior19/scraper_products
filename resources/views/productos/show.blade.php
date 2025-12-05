<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $producto->nombre }}</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <style>
        body { background: #f1f3f5; }
        .main-img { width: 100%; height: 380px; object-fit: cover; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .thumb-img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid #e9ecef; cursor: pointer; transition: .2s; }
        .thumb-img:hover { border-color: #0d6efd; transform: scale(1.05); }
        .info-box { background: #ffffff; padding: 25px; border-radius: 12px; box-shadow: 0 3px 10px rgba(0,0,0,0.10); }
        .price { font-size: 1.8rem; font-weight: 700; color: #0d6efd; }
        .badge-category { background: #dee2e6; padding: 5px 12px; border-radius: 20px; font-size: .8rem; }
    </style>

    <script>
        function cambiarImagen(src) {
            document.getElementById("main-image").src = src;
        }
    </script>
</head>

<body>

<div class="container py-4">

    <a href="{{ route('productos.index') }}" class="btn btn-outline-secondary mb-3">
        &larr; Volver a la lista
    </a>

    <div class="row g-4">

        <!-- IMÁGENES -->
        <div class="col-lg-5">

            <div class="mb-3">
                @php
                    $img = $producto->imagenes->first();
                    $src = $img ? $img->url_original : 'https://via.placeholder.com/500x350?text=Sin+Imagen';
                @endphp

                <img id="main-image" src="{{ $src }}" class="main-img">
            </div>

            <!-- MINIATURAS -->
            <div class="d-flex flex-wrap gap-2">
                @foreach($producto->imagenes as $img)
                    <img src="{{ $img->url_original }}"
                         class="thumb-img"
                         onclick="cambiarImagen('{{ $img->url_original }}')">
                @endforeach
            </div>

        </div>

        <!-- INFORMACIÓN -->
        <div class="col-lg-7">
            <div class="info-box">

                <span class="badge-category">
                    {{ $producto->categoria->nombre ?? 'Sin categoría' }}
                </span>

                <h1 class="h3 mt-2">{{ $producto->nombre }}</h1>

                <!-- PRECIO EN GUARANÍES -->
                <p class="price mt-2">
                    @if(!is_null($producto->precio))
                        ₲ {{ number_format($producto->precio, 0, ',', '.') }}
                    @else
                        <span class="text-muted">Precio no disponible</span>
                    @endif
                </p>

                <!-- PRECIO USD SI EXISTE -->
                @if(isset($producto->extra_json['precio_usd']))
                    <p><strong>Precio USD:</strong> {{ number_format($producto->extra_json['precio_usd'], 2) }}</p>
                @endif

                @if($producto->sku)
                    <p><strong>SKU:</strong> {{ $producto->sku }}</p>
                @endif

                @if($producto->descripcion)
                    <h5 class="mt-4">Descripción</h5>
                    <p>{!! $producto->descripcion !!}</p>
                @endif

                <!-- ATRIBUTOS ADICIONALES -->
                @if(isset($producto->extra_json['atributos']) && count($producto->extra_json['atributos']) > 0)
                    <h5 class="mt-4">Atributos</h5>
                    <ul>
                        @foreach($producto->extra_json['atributos'] as $key => $val)
                            <li><strong>{{ $key }}:</strong> {{ $val }}</li>
                        @endforeach
                    </ul>
                @endif

                <h5 class="mt-4">Enlace original</h5>
                <p>
                    <a href="{{ $producto->url_producto }}"
                       target="_blank">
                        {{ $producto->url_producto }}
                    </a>
                </p>

            </div>
        </div>
    </div>

</div>

</body>
</html>
