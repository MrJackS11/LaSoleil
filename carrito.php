<?php
require 'includes/conexion.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Lógica para SUMAR cantidad en el carrito
if (isset($_GET['sumar'])) {
    $id = $_GET['sumar'];
    $res = $conn->query("SELECT stock FROM productos WHERE id = $id");
    $prod = $res->fetch_assoc();
    
    // Solo sumamos si no superamos el stock
    if ($_SESSION['carrito'][$id]['cantidad'] < $prod['stock']) {
        $_SESSION['carrito'][$id]['cantidad']++;
    }
    header("Location: carrito.php");
    exit;
}

// Lógica para RESTAR cantidad en el carrito
if (isset($_GET['restar'])) {
    $id = $_GET['restar'];
    if ($_SESSION['carrito'][$id]['cantidad'] > 1) {
        $_SESSION['carrito'][$id]['cantidad']--;
    } else {
        // Si llega a 0, lo eliminamos
        unset($_SESSION['carrito'][$id]);
    }
    header("Location: carrito.php");
    exit;
}

// Lógica para ELIMINAR producto
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    unset($_SESSION['carrito'][$id]);
    header("Location: carrito.php");
    exit;
}

// Lógica para VACIAR carrito
if (isset($_GET['vaciar'])) {
    unset($_SESSION['carrito']);
    header("Location: carrito.php");
    exit;
}

require 'includes/header.php';
?>

<h2 style="color: var(--rosa-oscuro); display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-cart-shopping"></i> Tu Carrito</h2>

<?php if (!empty($_SESSION['carrito'])): ?>
    <div class="table-responsive">
    <table>
        <tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th><th>Acción</th></tr>
        <?php 
        $total = 0;
        foreach ($_SESSION['carrito'] as $id => $prod): 
            $subtotal = $prod['precio'] * $prod['cantidad'];
            $total += $subtotal;
        ?>
        <tr>
            <td><strong><?php echo htmlspecialchars($prod['nombre']); ?></strong></td>
            <td>Bs <?php echo number_format($prod['precio'], 2, ',', '.'); ?></td>
            
            <!-- CONTROLES DE CANTIDAD -->
            <td>
                <div style="display: flex; align-items: center; gap: 8px; justify-content: center;">
                    <a href="carrito.php?restar=<?php echo $id; ?>" class="btn-counter btn-counter-minus" aria-label="Restar"><i class="fa-solid fa-minus"></i></a>
                    <strong style="font-size: 1.1em; min-width: 20px; text-align: center;"><?php echo $prod['cantidad']; ?></strong>
                    <a href="carrito.php?sumar=<?php echo $id; ?>" class="btn-counter btn-counter-plus" aria-label="Sumar"><i class="fa-solid fa-plus"></i></a>
                </div>
            </td>

            <td style="font-weight: bold; color: var(--rosa-oscuro);">Bs <?php echo number_format($subtotal, 2, ',', '.'); ?></td>
            <td><a href="carrito.php?eliminar=<?php echo $id; ?>" class="btn btn-danger btn-icon" title="Eliminar"><i class="fa-solid fa-trash-can"></i></a></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
    
    <div style="text-align: right; margin-top: 20px; display: flex; flex-direction: column; align-items: flex-end; gap: 12px;">
        <h3 style="margin: 0; font-size: 1.4em; color: var(--rosa-oscuro);">Total: Bs <?php echo number_format($total, 2, ',', '.'); ?></h3>
        <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">
            <a href="index.php" class="btn btn-neutral"><i class="fa-solid fa-arrow-left"></i> Seguir Comprando</a>
            <a href="carrito.php?vaciar=true" class="btn btn-danger" onclick="return confirm('¿Seguro que deseas vaciar el carrito?');"><i class="fa-solid fa-trash"></i> Vaciar Carrito</a>
            <a href="procesar_pago.php" class="btn btn-primary"><i class="fa-solid fa-file-invoice"></i> Finalizar Compra (PDF)</a>
        </div>
    </div>
<?php else: ?>
    <div style="text-align: center; padding: 40px 20px; background: white; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); max-width: 450px; margin: 30px auto;">
        <i class="fa-solid fa-cart-arrow-down" style="font-size: 3em; color: var(--rosa-fuerte); margin-bottom: 15px;"></i>
        <p style="font-size: 1.2em; color: #666; margin-bottom: 20px;">Tu carrito está actualmente vacío.</p>
        <a href="index.php" class="btn btn-primary"><i class="fa-solid fa-bag-shopping"></i> Explorar Menú</a>
    </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>