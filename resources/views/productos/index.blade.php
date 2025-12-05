<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Productos recolectados</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container py-4">
    <h1 class="mb-4">Productos recolectados</h1>

    <form method="GET" class="mb-3">
        <div class="input-group">
            <input type="text"
                   name="q"
                   class="form-control"
                   placeholder="Buscar por nombre..."
                   value="{{ request('q') }}">
            <button class="btn btn-primary" type="submit">Buscar</button>
        </div>
    </form>

    @if($productos->count() === 0)
        <div class="alert alert-info">
            Todavía no hay productos. Ejecutá:<br>
            <code>php artisan scrape:categoria &lt;url&gt; "Nombre categoría"</code>
        </div>
    @endif

    <div class="row g-3">
        @foreach($productos as $producto)
            <div class="col-md-3 col-sm-6">
                <div class="card h-100">
                    @if($producto->imagenes->first())
                        <img src="{{ asset('storage/'.$producto->imagenes->first()->ruta_local) }}"
                             class="card-img-top"
                             style="height:180px; object-fit:cover;">
                    @endif
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title" style="font-size: 0.95rem;">
                            {{ Str::limit($producto->nombre, 60) }}
                        </h5>
                        <p class="card-text mb-1">
                            @if(!is_null($producto->precio))
                                <strong>USD {{ number_format($producto->precio, 2) }}</strong>
                            @else
                                <span class="text-muted">Precio no detectado</span>
                            @endif
                        </p>
                        <p class="card-text text-muted mb-2" style="font-size:0.8rem;">
                            {{ $producto->categoria->nombre ?? 'Sin categoría' }}
                        </p>
                        <a href="{{ route('productos.show', $producto->id) }}"
                           class="mt-auto btn btn-sm btn-outline-primary">
                            Ver detalles
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $productos->withQueryString()->links() }}
    </div>
</div>

</body>
</html>
