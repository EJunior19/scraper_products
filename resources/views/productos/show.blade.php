<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $producto->nombre }}</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <style>
        body { background:#0b1120; color:#e5e7eb; }

        .card-dark{
            background: radial-gradient(circle at top left, #111827 0, #020617 55%);
            border: 1px solid rgba(148, 163, 184, 0.20);
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.35);
        }

        .main-img{
            width: 100%;
            height: 380px;
            object-fit: contain;
            border-radius: 12px;
            background:#020617;
            border:1px solid rgba(148,163,184,0.20);
        }

        .thumb-img{
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid rgba(148,163,184,0.20);
            cursor: pointer;
            transition: .2s;
            background:#020617;
        }
        .thumb-img:hover{
            border-color:#38bdf8;
            transform: scale(1.05);
        }

        .badge-category{
            background: rgba(56, 189, 248, .12);
            color:#7dd3fc;
            border:1px solid rgba(56,189,248,.25);
            padding: 5px 12px;
            border-radius: 999px;
            font-size: .8rem;
            display:inline-flex;
            align-items:center;
            gap:6px;
        }

        .price{
            font-size: 1.8rem;
            font-weight: 800;
            color: #22c55e;
        }

        .muted { color:#9ca3af; }

        .desc-box{
            background:#020617;
            border:1px solid rgba(148,163,184,0.20);
            border-radius: 12px;
            padding: 14px;
            color:#e5e7eb;
        }

        a { color:#38bdf8; }
        a:hover { color:#22c55e; }

        .btn-primary{
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            border: none;
        }
        .btn-primary:hover{
            background: linear-gradient(135deg, #22c55e, #0ea5e9);
        }
        .btn-outline-light{
            border-color: rgba(148,163,184,0.35);
            color:#e5e7eb;
        }
        .btn-outline-light:hover{
            background:#111827;
            border-color:#38bdf8;
            color:#fff;
        }
    </style>

    <script>
        function cambiarImagen(src) {
            const main = document.getElementById("main-image");
            if (main) main.src = src;
        }
    </script>
</head>

<body>

<div class="container py-4">

    <a href="{{ route('productos.index') }}" class="btn btn-outline-light mb-3">
        &larr; Volver a la lista
    </a>

    <div class="row g-4">

        <!-- IMÁGENES -->
        <div class="col-lg-5">
            <div class="card-dark p-3">

                @php
                    $img = $producto->imagenes->first();
                    $src = $img ? $img->url_original : 'https://via.placeholder.com/900x700?text=Sin+Imagen';
                @endphp

                <img id="main-image" src="{{ $src }}" class="main-img" alt="{{ $producto->nombre }}">

                <!-- MINIATURAS -->
                @if($producto->imagenes && $producto->imagenes->count() > 1)
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @foreach($producto->imagenes as $img)
                            <img src="{{ $img->url_original }}"
                                 class="thumb-img"
                                 alt="Miniatura"
                                 onclick="cambiarImagen('{{ $img->url_original }}')">
                        @endforeach
                    </div>
                @endif

                <div class="mt-3 d-flex justify-content-between align-items-center">
                    @if($producto->sku)
                        <span class="muted" style="font-size:.9rem;">
                            SKU: <strong class="text-white">{{ $producto->sku }}</strong>
                        </span>
                    @else
                        <span class="muted" style="font-size:.9rem;">SKU: —</span>
                    @endif

                    @if($producto->categoria?->nombre)
                        <span class="badge-category">
                            🏷️ {{ $producto->categoria->nombre }}
                        </span>
                    @endif
                </div>

            </div>
        </div>

        <!-- INFORMACIÓN -->
        <div class="col-lg-7">
            <div class="card-dark p-4">

                <h1 class="h3 mb-2 text-white">{{ $producto->nombre }}</h1>

                <!-- PRECIO EN GUARANÍES -->
                <p class="price mb-2">
                    @if(!is_null($producto->precio))
                        ₲ {{ number_format($producto->precio, 0, ',', '.') }}
                    @else
                        <span class="muted">Precio no disponible</span>
                    @endif
                </p>

                <!-- USD / BRL si existen -->
                <div class="muted" style="font-size:.95rem;">
                    @if(isset($producto->extra_json['precio_usd']) && $producto->extra_json['precio_usd'])
                        <div><strong class="text-white">USD:</strong> {{ number_format($producto->extra_json['precio_usd'], 2, ',', '.') }}</div>
                    @endif

                    @if(isset($producto->extra_json['precio_brl']) && $producto->extra_json['precio_brl'])
                        <div><strong class="text-white">BRL:</strong> {{ number_format($producto->extra_json['precio_brl'], 2, ',', '.') }}</div>
                    @endif
                </div>

                <!-- DESCRIPCIÓN -->
                @if($producto->descripcion)
                    <h5 class="mt-4 text-white">Descripción</h5>
                    <div class="desc-box">
                        {{ $producto->descripcion }}
                    </div>
                @endif

                <!-- ATRIBUTOS ADICIONALES -->
                @if(isset($producto->extra_json['atributos']) && is_array($producto->extra_json['atributos']) && count($producto->extra_json['atributos']) > 0)
                    <h5 class="mt-4 text-white">Atributos</h5>
                    <ul class="mb-0">
                        @foreach($producto->extra_json['atributos'] as $key => $val)
                            <li>
                                <strong class="text-white">{{ $key }}:</strong>
                                <span class="muted">{{ is_array($val) ? json_encode($val) : $val }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <!-- LINK ORIGINAL -->
                @if(!empty($producto->url_producto))
                    <h5 class="mt-4 text-white">Enlace original</h5>
                    <a href="{{ $producto->url_producto }}" target="_blank" rel="noopener">
                        {{ $producto->url_producto }}
                    </a>
                @endif

            </div>
        </div>

    </div>

</div>

</body>
</html>
