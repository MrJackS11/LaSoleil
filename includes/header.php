<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}
// Contador del carrito
$total_items = 0;
if(isset($_SESSION['carrito'])) { 
    foreach($_SESSION['carrito'] as $item) { 
        $total_items += $item['cantidad']; 
    } 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Soleil - Panadería y Repostería</title>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    
</head>
<body>

<!-- REPRODUCTOR DE MÚSICA LA SOLEIL -->
<audio id="musicaFondo"></audio>

<div class="reproductor-container">
    
    <!-- El Panel Desplegable Glassmorphic -->
    <div class="playlist-panel" id="playlistPanel">
        <div class="playlist-header">
            <h4 class="playlist-header-title"><i class="fa-solid fa-compact-disc"></i> Playlist La Soleil</h4>
            <button onclick="togglePanel()" class="btn-close-music" title="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <!-- Tarjeta En Reproducción con Ecualizador Animado -->
        <div class="now-playing-box">
            <div class="now-playing-icon" id="nowPlayingDisc">
                <i class="fa-solid fa-compact-disc"></i>
            </div>
            <div class="now-playing-info">
                <span class="now-playing-badge" id="nowPlayingBadge">EN PAUSA</span>
                <span class="now-playing-title" id="nowPlayingTitle">Ciudad Fayenza</span>
            </div>
            <div class="mini-equalizer" id="equalizerAnim">
                <span></span><span></span><span></span><span></span>
            </div>
        </div>

        <!-- Botón Play/Pause dentro del panel -->
        <button id="btnPlayPause" onclick="toggleMusica()" class="btn btn-music-play">
            <i class="fa-solid fa-play"></i> Reproducir Música
        </button>

        <!-- Lista de canciones -->
        <ul id="listaUI" class="canciones-lista"></ul>

        <!-- CONTROL DE VOLUMEN -->
        <div class="volumen-container">
            <i class="fa-solid fa-volume-low"></i>
            <input type="range" id="volumenControl" class="volumen-slider" min="0" max="1" step="0.05" value="0.5" title="Ajustar volumen">
            <i class="fa-solid fa-volume-high"></i>
        </div>
    </div>

    <!-- Botón Flotante Principal con Ondas de Sonido -->
    <button id="btnMusica" class="btn-flotante" onclick="togglePanel()" title="Música de fondo" aria-label="Música de fondo">
        <span class="btn-flotante-label"><span class="live-dot"></span> Música</span>
        <i class="fa-solid fa-music"></i>
    </button>
</div>

<script>
    const listaCanciones = [
        { titulo: "Ciudad Fayenza", archivo: "assets/audio/ciudad_fayenza.mp3" },
        { titulo: "Pueblo Escaso", archivo: "assets/audio/littleroot_town.mp3" }
    ];
    
    let indiceActual = 0;
    let modoBucle = false; 
    const audio = document.getElementById('musicaFondo');
    const btnFlotante = document.getElementById('btnMusica');
    const btnPlayPause = document.getElementById('btnPlayPause');
    const listaUI = document.getElementById('listaUI');
    const panel = document.getElementById('playlistPanel');
    const volumenControl = document.getElementById('volumenControl');
    const nowPlayingBadge = document.getElementById('nowPlayingBadge');
    const nowPlayingTitle = document.getElementById('nowPlayingTitle');
    const nowPlayingDisc = document.getElementById('nowPlayingDisc');
    const equalizerAnim = document.getElementById('equalizerAnim');

    // Configurar volumen inicial
    audio.volume = 0.5;
    volumenControl.addEventListener('input', function() {
        audio.volume = this.value;
    });

    // Abrir y cerrar el panel al darle clic al botón flotante
    function togglePanel() {
        panel.classList.toggle('mostrar');
    }

    // Actualizar el diseño de la lista de canciones y estados
    function dibujarLista() {
        listaUI.innerHTML = '';
        listaCanciones.forEach((cancion, index) => {
            const li = document.createElement('li');
            li.className = 'cancion-item' + (index === indiceActual ? ' activa' : '');
            
            const span = document.createElement('span');
            span.style.display = 'flex';
            span.style.alignItems = 'center';
            span.style.gap = '8px';
            span.innerHTML = (index === indiceActual && !audio.paused) 
                ? '<i class="fa-solid fa-play" style="font-size:11px; color:var(--rosa-oscuro);"></i> ' + cancion.titulo 
                : '<i class="fa-solid fa-music" style="font-size:11px; color:#b08d9b;"></i> ' + cancion.titulo;
            span.onclick = () => reproducirCancion(index); 
            
            const btnLoop = document.createElement('button');
            btnLoop.className = 'btn-loop' + (modoBucle && index === indiceActual ? ' activo' : '');
            btnLoop.innerHTML = '<i class="fa-solid fa-repeat"></i>';
            btnLoop.title = 'Repetir en bucle';
            btnLoop.onclick = (e) => {
                e.stopPropagation(); 
                modoBucle = !modoBucle;
                audio.loop = modoBucle;
                dibujarLista(); 
            };
            
            li.appendChild(span);
            li.appendChild(btnLoop);
            listaUI.appendChild(li);
        });
        
        nowPlayingTitle.textContent = listaCanciones[indiceActual].titulo;

        // Actualizar botón de Play/Pause y animaciones del widget
        if(audio.paused) {
            btnPlayPause.innerHTML = '<i class="fa-solid fa-play"></i> Reproducir Música';
            btnFlotante.classList.remove('is-playing');
            btnFlotante.innerHTML = '<span class="btn-flotante-label"><span class="live-dot"></span> Música</span><i class="fa-solid fa-music"></i>';
            nowPlayingBadge.textContent = 'EN PAUSA';
            nowPlayingBadge.style.color = 'var(--texto-mutado)';
            nowPlayingDisc.classList.remove('spinning');
            equalizerAnim.classList.remove('active');
        } else {
            btnPlayPause.innerHTML = '<i class="fa-solid fa-pause"></i> Pausar Música';
            btnFlotante.classList.add('is-playing');
            btnFlotante.innerHTML = '<span class="btn-flotante-label"><span class="live-dot"></span> Reproduciendo</span><i class="fa-solid fa-compact-disc fa-spin"></i>';
            nowPlayingBadge.textContent = 'EN REPRODUCCIÓN';
            nowPlayingBadge.style.color = 'var(--rosa-oscuro)';
            nowPlayingDisc.classList.add('spinning');
            equalizerAnim.classList.add('active');
        }
    }

    function reproducirCancion(index) {
        indiceActual = index;
        audio.src = listaCanciones[indiceActual].archivo;
        audio.loop = modoBucle;
        audio.play().catch(e => console.log('Autoplay blocked:', e));
        dibujarLista();
    }

    // Cuando termina la canción, pasa a la siguiente si no está en bucle
    audio.addEventListener('ended', function() {
        if (!modoBucle) { 
            indiceActual = (indiceActual + 1) % listaCanciones.length;
            reproducirCancion(indiceActual);
        }
    });

    // Reproducir / Pausar desde el panel
    function toggleMusica() {
        if (audio.paused) {
            if (!audio.src) {
                reproducirCancion(0);
            } else {
                audio.play().catch(e => console.log('Autoplay blocked:', e));
                dibujarLista();
            }
        } else {
            audio.pause();
            dibujarLista();
        }
    }

    dibujarLista();
</script>

<!-- HEADER PRINCIPAL -->
<header class="main-header">
    <!-- Logo centrado y responsivo -->
    <a href="index.php" style="text-decoration: none; display: inline-block;">
        <img src="assets/img/logo.png" alt="La Soleil" class="header-logo" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'">
    </a>
    
    <!-- Botones de navegación estilo píldoras modernas -->
    <nav class="header-nav">
        <a href="index.php" class="btn-header"><i class="fa-solid fa-house"></i> Inicio</a>
        <a href="estadisticas.php" class="btn-header"><i class="fa-solid fa-chart-pie"></i> Estadísticas</a>
        <a href="inventario.php" class="btn-header"><i class="fa-solid fa-boxes-stacked"></i> Inventario</a>
        <a href="admin.php" class="btn-header"><i class="fa-solid fa-circle-plus"></i> Nuevo</a>
        <a href="editar.php" class="btn-header"><i class="fa-solid fa-sliders"></i> Panel</a>
        <a href="carrito.php" class="btn-header btn-header-cart"><i class="fa-solid fa-cart-shopping"></i> Carrito <span class="cart-badge"><?php echo $total_items; ?></span></a>
    </nav>
</header>

<div class="container">