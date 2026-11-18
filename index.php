<?php
require 'includes/conexion.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Lógica para añadir al carrito con cantidad exacta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_carrito'])) {
    $id = $_POST['id'];
    $cantidad = (int)$_POST['cantidad'];
    
    $res = $conn->query("SELECT * FROM productos WHERE id = $id");
    if ($res->num_rows > 0) {
        $prod = $res->fetch_assoc();
        $en_carrito = isset($_SESSION['carrito'][$id]) ? $_SESSION['carrito'][$id]['cantidad'] : 0;
        
        // Verificamos que no exceda el stock disponible
        if (($en_carrito + $cantidad) <= $prod['stock']) {
            if (isset($_SESSION['carrito'][$id])) {
                $_SESSION['carrito'][$id]['cantidad'] += $cantidad;
            } else {
                $_SESSION['carrito'][$id] = ['nombre' => $prod['nombre'], 'precio' => $prod['precio'], 'cantidad' => $cantidad];
            }
        }
    }
    header("Location: index.php?cat=" . ($_GET['cat'] ?? 'Todas') . "&buscar=" . ($_GET['buscar'] ?? ''));
    exit;
}

$categoria_actual = $_GET['cat'] ?? 'Todas';
$busqueda = $_GET['buscar'] ?? '';

// Lógica del buscador y filtros
$sql = "SELECT * FROM productos WHERE 1=1"; // 1=1 es un truco para facilitar concatenar
if ($categoria_actual != 'Todas') {
    $sql .= " AND categoria = '$categoria_actual'";
}
if (!empty($busqueda)) {
    $sql .= " AND (nombre LIKE '%$busqueda%' OR descripcion LIKE '%$busqueda%')";
}

$resultado = $conn->query($sql);
require 'includes/header.php';

?>
<?php if(isset($_SESSION['compra_exitosa'])): ?>
    <div style="background: #fff5f8; color: var(--rosa-oscuro); padding: 15px; border-radius: 12px; text-align: center; margin-bottom: 20px; border: 1.5px solid var(--rosa-fuerte); box-shadow: 0 2px 8px rgba(216,112,147,0.15); display: flex; align-items: center; justify-content: center; gap: 8px;">
        <i class="fa-solid fa-circle-check" style="font-size: 1.2em; color: var(--rosa-oscuro);"></i> ¡Gracias por tu compra! Tu pedido ha sido registrado y tu factura ha sido generada.
    </div>
    <?php unset($_SESSION['compra_exitosa']); // Borramos el mensaje para que no salga siempre ?>
<?php endif; ?>

<!-- BARRA DE BÚSQUEDA -->
<div style="text-align: center; margin-bottom: 20px;">
    <form method="GET" action="index.php" style="display: inline-block; width: 100%; max-width: 500px;">
        <input type="hidden" name="cat" value="<?php echo $categoria_actual; ?>">
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" name="buscar" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar pan, pastel, galleta..." style="flex-grow: 1; padding: 12px 18px; border-radius: 30px; border: 1.5px solid var(--rosa-fuerte); font-family: inherit; font-size: 0.95em; outline: none; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <button type="submit" class="btn btn-search" aria-label="Buscar"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
    </form>
</div>

<!-- FILTROS -->
<div class="filtros">
    <?php 
    $cats = ['Todas', 'Panes', 'Masitas', 'Galletas', 'Postres', 'Bebidas'];
    foreach($cats as $c) {
        $active = ($categoria_actual == $c) ? 'active' : '';
        // Conservamos la búsqueda al cambiar de categoría
        echo "<a href='index.php?cat=$c&buscar=$busqueda' class='$active'>$c</a>";
    }
    ?>
</div>

<div class="grid">
    <?php if ($resultado->num_rows > 0): ?>
        <?php while($p = $resultado->fetch_assoc()): 
            $en_carrito = isset($_SESSION['carrito'][$p['id']]) ? $_SESSION['carrito'][$p['id']]['cantidad'] : 0;
            $stock_disponible = $p['stock'] - $en_carrito;
        ?>
            <div class="card">
                <img src="assets/img/<?php echo htmlspecialchars($p['imagen']); ?>" onerror="this.src='assets/img/default.jpg'">
                <div class="card-content">
                    <span class="categoria-tag"><?php echo htmlspecialchars($p['categoria']); ?></span>
                    <h3><?php echo htmlspecialchars($p['nombre']); ?></h3>
                    <p><?php echo htmlspecialchars($p['descripcion']); ?></p>
                    <div class="precio">Bs <?php echo number_format($p['precio'], 2, ',', '.'); ?></div>
                    
                    <div style="font-size: 0.85em; color: #666; margin-bottom: 10px;">
                        Existencias: <strong><?php echo $stock_disponible; ?></strong>
                    </div>

                    <?php if ($stock_disponible > 0): ?>
                        <form method="POST" action="index.php" style="display: flex; gap: 8px; margin-top: auto; align-items: center;">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <!-- Selector de cantidad -->
                            <input type="number" name="cantidad" value="1" min="1" max="<?php echo $stock_disponible; ?>" style="width: 55px; padding: 9px 5px; border-radius: 8px; border: 1.5px solid #e0e0e0; text-align: center; font-weight: bold; font-family: inherit;">
                            <button type="submit" name="agregar_carrito" class="btn-add" style="flex-grow: 1;"><i class="fa-solid fa-cart-plus"></i> Añadir</button>
                        </form>
                    <?php else: ?>
                        <button class="btn-add btn-disabled" disabled><i class="fa-solid fa-ban"></i> Agotado</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="grid-column: 1 / -1; text-align: center;">No se encontraron productos con esos filtros.</p>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>