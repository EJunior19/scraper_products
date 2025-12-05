<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $producto->nombre }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container py-4">

    <a href="{{ route('productos.index') }}" class="btn btn-link mb-3">&larr; Volver a la lista</a>

    <div class="row">
        <div class="col-md-5">
            @if($producto->imagenes->count())
                <div class="mb-3">
                    <img src="{{ asset('storage/'.$producto->imagenes->first()->ruta_local) }}"
                         class="img-fluid rounded border">
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @foreach($producto->imagenes->slice(1) as $img)
                        <img src="{{ asset('storage/'.$img->ruta_local) }}"
                             class="img-thumbnail"
                             style="width: 80px; height: 80px; object-fit:cover;">
                    @endforeach
                </div>
            @else
                <div class="alert alert-secondary">
                    Sin imágenes guardadas.
                </div>
            @endif
        </div>

        <div class="col-md-7">
            <h1 class="h3">{{ $producto->nombre }}</h1>

            <p class="text-muted">
                Categoría:
                <strong>{{ $producto->categoria->nombre ?? 'Sin categoría' }}</strong>
            </p>

            <p>
                @if(!is_null($producto->precio))
                    <span class="h4">USD {{ number_format($producto->precio, 2) }}</span>
                @else
                    <span class="text-muted">Precio no detectado</span>
                @endif
            </p>

            @if($producto->sku)
                <p><strong>SKU:</strong> {{ $producto->sku }}</p>
            @endif

            @if($producto->descripcion)
                <h5 class="mt-4">Descripción</h5>
                <p>{{ $producto->descripcion }}</p>
            @endif

            <h5 class="mt-4">Enlace original</h5>
            <p>
                <a href="{{ $producto->url_producto }}" target="_blank">
                    {{ $producto->url_producto }}
                </a>
            </p>
        </div>
    </div>

</div>

</body>
</html>
