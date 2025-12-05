<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $producto->nombre }}</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <style>
        body {
            background: #f1f3f5;
        }

        .main-img {
            width: 100%;
            height: 380px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .thumb-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            cursor: pointer;
            transition: 0.2s ease-in-out;
        }

        .thumb-img:hover {
            border-color: #0d6efd;
            transform: scale(1.05);
        }

        .info-box {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.10);
        }

        .price {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0d6efd;
        }

        .badge-category {
            background: #dee2e6;
            padding: 5px 12px;
            font-size: 0.8rem;
            border-radius: 20px;
        }

        .back-btn {
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
    </style>

    <script>
        function cambiarImagen(src) {
            document.getElementById("main-image").src = src;
        }
    </script>
</head>

<body>

<div class="container py-4">

    <a href="{{ route('productos.index') }}" class="btn btn-outline-secondary back-btn">
        &larr; Volver a la lista
    </a>

    <div class="row g-4">
        <!-- Columna izquierda: Imágenes -->
        <div class="col-lg-5">

            <div class="mb-3">
                @if($producto->imagenes->count())
                    <img id="main-image"
                         src="{{ asset('storage/'.$producto->imagenes->first()->ruta_local) }}"
                         class="main-img">
                @else
                    <img id="main-image"
                         src="https://via.placeholder.com/500x350?text=Sin+Imagen"
                         class="main-img">
                @endif
            </div>

            <!-- Miniaturas -->
            <div class="d-flex flex-wrap gap-2">
                @foreach($producto->imagenes as $img)
                    <img src="{{ asset('storage/'.$img->ruta_local) }}"
                         class="thumb-img"
                         onclick="cambiarImagen('{{ asset('storage/'.$img->ruta_local) }}')">
                @endforeach
            </div>
        </div>

        <!-- Columna derecha: Información -->
        <div class="col-lg-7">
            <div class="info-box">

                <span class="badge-category">
                    {{ $producto->categoria->nombre ?? 'Sin categoría' }}
                </span>

                <h1 class="h3 mt-2">{{ $producto->nombre }}</h1>

                <p class="price mt-2">
                    @if(!is_null($producto->precio))
                        USD {{ number_format($producto->precio, 2) }}
                    @else
                        <span class="text-muted" style="font-size:1rem;">Precio no disponible</span>
                    @endif
                </p>

                @if($producto->sku)
                    <p class="mt-2">
                        <strong>SKU:</strong> {{ $producto->sku }}
                    </p>
                @endif

                @if($producto->descripcion)
                    <h5 class="mt-4">Descripción</h5>
                    <p>{{ $producto->descripcion }}</p>
                @endif

                <h5 class="mt-4">Enlace original</h5>
                <p>
                    <a href="{{ $producto->url_producto }}"
                       target="_blank"
                       class="text-decoration-none">
                        {{ $producto->url_producto }}
                    </a>
                </p>

            </div>
        </div>
    </div>

</div>

</body>
</html>
