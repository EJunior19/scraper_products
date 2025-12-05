<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Productos Recolectados</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <style>
        body { background: #f5f6f8; }
        .product-card { transition: transform .2s, box-shadow .2s; border-radius: 12px; overflow: hidden; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); }
        .product-img { height: 200px; object-fit: cover; width: 100%; }
        .category-tag { background: #eef2ff; border-radius: 50px; padding: 4px 12px; font-size: 0.75rem; color: #3b5bdb; margin-bottom: 8px; display: inline-block; }
        .price { font-weight: bold; color: #0d6efd; }
        .floating-btn { position: fixed; bottom: 25px; right: 25px; z-index: 999; }
    </style>
</head>

<body>

<div class="container py-4">

    <!-- MENSAJES -->
    @if(session('success'))
        <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger shadow-sm">{{ session('error') }}</div>
    @endif

    <!-- PANEL SCRAPER -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Scrapear nueva categoría</h5>

            <form action="{{ route('productos.scrapear') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="url"
                               name="url"
                               class="form-control"
                               placeholder="URL de categoría (ej: https://www.mapy.com.py/categoria-produto/...)"
                               required>
                    </div>

                    <div class="col-md-4">
                        <input type="text"
                               name="categoria"
                               class="form-control"
                               placeholder="Nombre categoría"
                               required>
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">Iniciar Scraping</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- TITULO -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold">Productos Recolectados</h1>
    </div>

    <!-- BUSCADOR -->
    <form method="GET" class="mb-4">
        <div class="input-group shadow-sm">
            <input type="text" name="q" class="form-control"
                   placeholder="Buscar por nombre..." value="{{ request('q') }}">
            <button class="btn btn-primary">Buscar</button>
        </div>
    </form>

    <!-- SIN PRODUCTOS -->
    @if($productos->count() === 0)
        <div class="alert alert-info shadow-sm">
            Todavía no hay productos scrapeados.<br>
            Podés usar el panel superior para comenzar.
        </div>
    @endif

    <!-- GRID -->
    <div class="row g-4">
        @foreach($productos as $producto)
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="card product-card h-100 shadow-sm">

                    @php
                        $img = $producto->imagenes->first();
                        $src = $img ? $img->url_original : 'https://via.placeholder.com/500x300?text=Sin+Imagen';
                    @endphp

                    <img src="{{ $src }}" class="product-img">

                    <div class="card-body d-flex flex-column">

                        <span class="category-tag">{{ $producto->categoria->nombre ?? "Sin categoría" }}</span>

                        <h5 class="card-title mb-1" style="font-size: 0.95rem;">
                            {{ Str::limit($producto->nombre, 60) }}
                        </h5>

                        <p class="price mb-2">
                            @if(!is_null($producto->precio))
                                ₲ {{ number_format($producto->precio, 0, ',', '.') }}
                            @else
                                <span class="text-muted">Precio no disponible</span>
                            @endif
                        </p>

                        <a href="{{ route('productos.show', $producto->id) }}"
                           class="btn btn-outline-primary btn-sm mt-auto">
                            Ver detalles
                        </a>

                    </div>

                </div>
            </div>
        @endforeach
    </div>

    <!-- PAGINACIÓN -->
    <div class="mt-4 d-flex justify-content-center">
        {{ $productos->withQueryString()->links() }}
    </div>

</div>

<a href="/productos" class="btn btn-primary rounded-circle floating-btn shadow">↻</a>

</body>
</html>
