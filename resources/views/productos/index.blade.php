<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Productos Recolectados</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <style>
        body {
            background: #0b1120;
            color: #e5e7eb;
        }

        .page-title {
            color: #f9fafb;
        }

        .card {
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            background: radial-gradient(circle at top left, #111827 0, #020617 55%);
        }

        .product-card {
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.7);
            border-color: #38bdf8;
        }

        .product-img {
            height: 210px;
            object-fit: cover;
            width: 100%;
            background: #020617;
        }

        .category-tag {
            background: rgba(56, 189, 248, .1);
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 0.72rem;
            color: #7dd3fc;
            margin-bottom: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .category-tag svg {
            width: 12px;
            height: 12px;
            flex: 0 0 auto;
        }

        .price {
            font-weight: 700;
            color: #22c55e;
            font-size: 0.98rem;
        }

        .sku-pill {
            font-size: 0.75rem;
            color: #9ca3af;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            padding: 2px 8px;
            display: inline-block;
        }

        .floating-btn {
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 999;
        }

        .input-dark {
            background: #020617;
            border-color: #1f2937;
            color: #e5e7eb;
        }

        .input-dark::placeholder {
            color: #6b7280;
        }

        .input-dark:focus {
            background: #020617;
            border-color: #38bdf8;
            color: #f9fafb;
            box-shadow: 0 0 0 .15rem rgba(56, 189, 248, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #22c55e, #0ea5e9);
        }

        .badge-counter {
            font-size: 0.8rem;
            color: #9ca3af;
        }

        /* ✅ Ajuste visual para paginación bootstrap en tema oscuro */
        .pagination .page-link {
            background: #0b1222;
            border-color: rgba(148, 163, 184, 0.25);
            color: #e5e7eb;
        }
        .pagination .page-link:hover {
            background: #111827;
            border-color: rgba(56, 189, 248, 0.6);
            color: #f9fafb;
        }
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            border-color: transparent;
            color: #ffffff;
        }
        .pagination .page-item.disabled .page-link {
            background: #0b1222;
            color: #6b7280;
        }
    </style>
</head>

<body>

<div class="container py-4">

    {{-- MENSAJES --}}
    @if(session('success'))
        <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger shadow-sm">{{ session('error') }}</div>
    @endif

    {{-- PANEL SCRAPER --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-3 text-white">Scrapear nueva categoría</h5>

            <form action="{{ route('productos.scrapear') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="url"
                               name="url"
                               class="form-control input-dark"
                               placeholder="URL de categoría (ej: https://www.mapy.com.py/categoria-produto/...)"
                               required>
                    </div>

                    <div class="col-md-4">
                        <input type="text"
                               name="categoria"
                               class="form-control input-dark"
                               placeholder="Nombre categoría"
                               required>
                    </div>

                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary">
                            Iniciar Scraping
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- TÍTULO + RESUMEN --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h3 fw-bold page-title mb-0">Productos Recolectados</h1>

            <span class="badge-counter">
                @if($productos->total() > 0)
                    Mostrando {{ $productos->firstItem() }} a {{ $productos->lastItem() }} de {{ $productos->total() }} resultados
                @else
                    Mostrando 0 resultados
                @endif

                @if(request('q') || request('sku'))
                    — filtros activos
                @endif
            </span>
        </div>
    </div>

    {{-- BUSCADOR (nombre + SKU) --}}
    <form method="GET" class="mb-4">
        <div class="row g-2">
            <div class="col-md-6">
                <div class="input-group shadow-sm">
                    <span class="input-group-text input-dark">🔎</span>
                    <input type="text"
                           name="q"
                           class="form-control input-dark"
                           placeholder="Buscar por nombre..."
                           value="{{ request('q') }}">
                </div>
            </div>

            <div class="col-md-4">
                <div class="input-group shadow-sm">
                    <span class="input-group-text input-dark"># SKU</span>
                    <input type="text"
                           name="sku"
                           class="form-control input-dark"
                           placeholder="Buscar por SKU exacto o parcial..."
                           value="{{ request('sku') }}">
                </div>
            </div>

            <div class="col-md-2 d-grid">
                <button class="btn btn-primary shadow-sm">
                    Buscar
                </button>
            </div>
        </div>
    </form>

    {{-- SIN PRODUCTOS --}}
    @if($productos->count() === 0)
        <div class="alert alert-info shadow-sm">
            Todavía no hay productos scrapeados o no se encontraron resultados para tu búsqueda.<br>
            Podés usar el panel superior para comenzar.
        </div>
    @endif

    {{-- GRID DE PRODUCTOS --}}
    <div class="row g-4">
        @foreach($productos as $producto)
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="card product-card h-100 shadow-sm">

                    @php
                        $img = $producto->imagenes->first();
                        $src = $img ? $img->url_original : 'https://via.placeholder.com/500x300?text=Sin+Imagen';
                    @endphp

                    <img src="{{ $src }}" class="product-img" alt="{{ $producto->nombre }}">

                    <div class="card-body d-flex flex-column">

                        {{-- Categoría --}}
                        <span class="category-tag">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M2.5 5.5A2.5 2.5 0 0 1 5 3h3.586a2.5 2.5 0 0 1 1.768.732l6.414 6.414a2.5 2.5 0 0 1 0 3.536l-3.586 3.586a2.5 2.5 0 0 1-3.536 0L3.232 9.768A2.5 2.5 0 0 1 2.5 8V5.5Z" />
                            </svg>
                            {{ optional($producto->categoria)->nombre ?? "Sin categoría" }}
                        </span>

                        {{-- Nombre --}}
                        <h5 class="card-title mb-1" style="font-size: 0.95rem;">
                            {{ \Illuminate\Support\Str::limit($producto->nombre, 60) }}
                        </h5>

                        {{-- SKU --}}
                        @if(!empty($producto->sku))
                            <span class="sku-pill mb-2">
                                SKU: {{ $producto->sku }}
                            </span>
                        @endif

                        {{-- Precio --}}
                        <p class="price mb-2">
                            @if(!is_null($producto->precio))
                                ₲ {{ number_format($producto->precio, 0, ',', '.') }}
                            @else
                                <span class="text-muted">Precio no disponible</span>
                            @endif
                        </p>

                        <a href="{{ route('productos.show', $producto->id) }}"
                           class="btn btn-outline-light btn-sm mt-auto">
                            Ver detalles
                        </a>

                    </div>

                </div>
            </div>
        @endforeach
    </div>

    {{-- ✅ PAGINACIÓN (Bootstrap 5, así se arreglan las flechas gigantes) --}}
    @if($productos->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $productos->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    @endif

</div>

<a href="{{ route('productos.index') }}"
   class="btn btn-primary rounded-circle floating-btn shadow"
   title="Refrescar">
    ↻
</a>

</body>
</html>
