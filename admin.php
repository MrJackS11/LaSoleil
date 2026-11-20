<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'includes/conexion.php';

// Consultar ingredientes disponibles para la receta
$lista_ingredientes = $conn->query("SELECT * FROM ingredientes ORDER BY nombre ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $categoria = $_POST['categoria'] ?? 'Panes';
    $stock_inicial = isset($_POST['stock']) ? max(0, (int)$_POST['stock']) : 0;
    $requiere_receta = isset($_POST['requiere_receta']) ? 1 : 0;
    
    if (empty($nombre) || $precio <= 0) {
        $error = "Por favor ingresa un nombre válido y un precio mayor a 0.";
    } else {
        $imagen_nombre = 'default.jpg';
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            $directorio = __DIR__ . "/assets/img/";
            $nombre_archivo = preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['imagen']['name']));
            $imagen_nombre = time() . "_" . $nombre_archivo; 
            move_uploaded_file($_FILES['imagen']['tmp_name'], $directorio . $imagen_nombre);
        }

        // Procesar ingredientes de la receta si aplica
        $receta_items = []; // [ingrediente_id => cantidad_necesaria_por_unidad]
        if ($requiere_receta && isset($_POST['ingredientes']) && is_array($_POST['ingredientes'])) {
            foreach ($_POST['ingredientes'] as $idx => $ing_id) {
                $ing_id = (int)$ing_id;
                $cant = isset($_POST['cantidades'][$idx]) ? (float)$_POST['cantidades'][$idx] : 0;
                if ($ing_id > 0 && $cant > 0) {
                    if (isset($receta_items[$ing_id])) {
                        $receta_items[$ing_id] += $cant;
                    } else {
                        $receta_items[$ing_id] = $cant;
                    }
                }
            }
        }

        // Validaciones según el flujo logístico
        $errores_insumos = [];
        if ($requiere_receta) {
            if (empty($receta_items)) {
                $error = "Has marcado que el producto requiere receta, pero no has añadido ningún ingrediente con cantidad válida. Por favor selecciona al menos 1 ingrediente o desmarca la casilla de receta.";
            } elseif ($stock_inicial > 0) {
                // Comprobar si hay stock suficiente en almacén para elaborar las unidades iniciales
                foreach ($receta_items as $ing_id => $cant_x_unidad) {
                    $total_necesario = $cant_x_unidad * $stock_inicial;
                    $res_ing = $conn->query("SELECT nombre, stock_actual, unidad_medida FROM ingredientes WHERE id = $ing_id");
                    if ($res_ing && $row_ing = $res_ing->fetch_assoc()) {
                        if ($row_ing['stock_actual'] < $total_necesario) {
                            $errores_insumos[] = "<strong>" . htmlspecialchars($row_ing['nombre']) . "</strong>: necesitas " . number_format($total_necesario, 2, ',', '.') . " " . $row_ing['unidad_medida'] . " (disponible en bodega: " . number_format($row_ing['stock_actual'], 2, ',', '.') . " " . $row_ing['unidad_medida'] . ")";
                        }
                    }
                }

                if (!empty($errores_insumos)) {
                    $error = "Stock insuficiente en bodega para preparar las <strong>$stock_inicial unidades iniciales</strong>:<br>• " . implode("<br>• ", $errores_insumos) . "<br><small style='display:block; margin-top:8px; color:#5d4037;'>Sugerencia: Puedes colocar <strong>Stock Inicial: 0</strong> para registrar el producto con su receta y fabricarlo más adelante cuando repongas insumos.</small>";
                }
            }
        }

        // Si no hay errores, procedemos a insertar el producto, la receta y descontar insumos
        if (!isset($error)) {
            $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio, categoria, stock, imagen) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsis", $nombre, $descripcion, $precio, $categoria, $stock_inicial, $imagen_nombre);

            if ($stmt->execute()) {
                // Obtener ID del producto creado
                $res_id = $conn->query("SELECT MAX(id) as last_id FROM productos");
                $producto_id = (int)$res_id->fetch_assoc()['last_id'];

                // Registrar receta en la tabla recetas
                if ($requiere_receta && !empty($receta_items)) {
                    $stmt_rec = $conn->prepare("INSERT INTO recetas (producto_id, ingrediente_id, cantidad_necesaria) VALUES (?, ?, ?)");
                    foreach ($receta_items as $ing_id => $cant_x_unidad) {
                        $stmt_rec->bind_param("iid", $producto_id, $ing_id, $cant_x_unidad);
                        $stmt_rec->execute();

                        // Si se indicó stock inicial > 0, descontar ingredientes de la bodega
                        if ($stock_inicial > 0) {
                            $total_descontar = $cant_x_unidad * $stock_inicial;
                            $conn->query("UPDATE ingredientes SET stock_actual = stock_actual - $total_descontar WHERE id = $ing_id");
                        }
                    }
                }

                if ($stock_inicial > 0) {
                    $mensaje = "¡Producto <strong>'$nombre'</strong> agregado con éxito! Se registraron " . count($receta_items) . " ingredientes en su receta y se descontaron los insumos de bodega para las $stock_inicial unidades en vitrina.";
                } elseif ($requiere_receta) {
                    $mensaje = "¡Producto <strong>'$nombre'</strong> registrado con su receta en catálogo (0 existencias en vitrina)! Podrás hornear/fabricar lotes en la sección de Inventario.";
                } else {
                    $mensaje = "¡Producto <strong>'$nombre'</strong> agregado con éxito como producto directo sin receta!";
                }

                // Limpiar POST para resetear el formulario
                $_POST = [];
                // Refrescar lista de ingredientes para que muestre el stock actualizado
                $lista_ingredientes = $conn->query("SELECT * FROM ingredientes ORDER BY nombre ASC");
            } else {
                $error = "Error al guardar el producto en la base de datos: " . $conn->error;
            }
        }
    }
}

require 'includes/header.php';
?>

<div class="form-container" style="max-width: 680px;">
    <h2 style="color: var(--rosa-oscuro); text-align: center; display: flex; align-items: center; justify-content: center; gap: 8px;">
        <i class="fa-solid fa-circle-plus"></i> Agregar Nuevo Producto
    </h2>
    
    <?php if(isset($mensaje)): ?>
        <div style="background:#fff5f8; color:var(--rosa-oscuro); border:1.5px solid var(--rosa-fuerte); padding:14px 18px; border-radius:12px; text-align:center; margin-bottom:18px; box-shadow:0 2px 8px rgba(216,112,147,0.15); display:flex; align-items:center; justify-content:center; gap:8px;">
            <i class="fa-solid fa-circle-check" style="font-size: 1.2em;"></i> <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <?php if(isset($error)): ?>
        <div style="background:#fff5f8; color:#e11d48; border:1.5px solid #ffccd5; padding:14px 18px; border-radius:12px; text-align:left; margin-bottom:18px; box-shadow:0 2px 8px rgba(225,29,72,0.1);">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; font-weight:bold; font-size:1.05em;">
                <i class="fa-solid fa-triangle-exclamation"></i> Atención al procesar el producto:
            </div>
            <div style="font-size: 0.95em; line-height: 1.5;"><?php echo $error; ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="admin.php" enctype="multipart/form-data">
        
        <!-- SECCIÓN 1: DATOS BÁSICOS -->
        <h3 style="color: var(--rosa-oscuro); border-bottom: 1.5px solid var(--rosa-suave); padding-bottom: 8px; margin-top: 10px; font-size: 1.1em; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-tag"></i> 1. Información del Producto
        </h3>
        
        <div class="form-group">
            <label>Nombre del Producto:</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" placeholder="Ej: Croissant de Mantequilla" required>
        </div>
        
        <div class="form-group">
            <label>Descripción:</label>
            <textarea name="descripcion" rows="2" placeholder="Describe el producto, sabor o presentación..." required><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
        </div>
        
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 140px;">
                <label>Precio de Venta (Bs):</label>
                <input type="number" step="0.01" min="0.01" name="precio" value="<?php echo htmlspecialchars($_POST['precio'] ?? ''); ?>" placeholder="0.00" required>
            </div>
            
            <div class="form-group" style="flex: 1; min-width: 160px;">
                <label>Categoría:</label>
                <select name="categoria">
                    <?php 
                    $cats = ['Panes', 'Masitas', 'Galletas', 'Postres', 'Bebidas'];
                    $cat_sel = $_POST['categoria'] ?? 'Panes';
                    foreach($cats as $c) {
                        $s = ($cat_sel == $c) ? 'selected' : '';
                        echo "<option value='$c' $s>$c</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Imagen del producto:</label>
            <input type="file" name="imagen" accept="image/*">
            <small style="color: #888;">Opcional: Si no seleccionas archivo, se asignará una imagen por defecto.</small>
        </div>

        <hr style="border: 0; border-top: 1px dashed var(--rosa-fuerte); margin: 25px 0;">

        <!-- SECCIÓN 2: RECETA E INSUMOS -->
        <h3 style="color: var(--rosa-oscuro); border-bottom: 1.5px solid var(--rosa-suave); padding-bottom: 8px; margin-top: 0; font-size: 1.1em; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-mortar-pestle"></i> 2. Receta e Ingredientes (Por 1 Unidad)
        </h3>

        <div style="margin-bottom: 15px; background: #fffcfd; padding: 12px 15px; border-radius: 10px; border: 1.5px solid var(--rosa-suave);">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: bold; color: var(--texto);">
                <input type="checkbox" name="requiere_receta" id="toggleReceta" value="1" <?php echo (!isset($_POST['nombre']) || isset($_POST['requiere_receta'])) ? 'checked' : ''; ?> onchange="toggleSeccionReceta()" style="width: 18px; height: 18px; accent-color: var(--rosa-oscuro);">
                <span>Este producto se elabora con ingredientes de panadería / repostería</span>
            </label>
            <small style="color: #888; display: block; margin-top: 4px; margin-left: 28px;">Desmárcalo únicamente si es un producto de reventa directa sin preparación (ej. gaseosas embotelladas).</small>
        </div>

        <div id="seccionRecetaContenedor" style="<?php echo (isset($_POST['nombre']) && !isset($_POST['requiere_receta'])) ? 'display: none;' : ''; ?>">
            <p style="font-size: 0.88em; color: var(--texto-mutado); margin: 0 0 10px 0;">
                Especifica la cantidad de cada ingrediente requerida para preparar <strong>1 sola unidad</strong>:
            </p>

            <div id="contenedorIngredientesReceta" style="background: #fff8f8; padding: 15px; border-radius: 12px; border: 1.5px dashed var(--rosa-fuerte); margin-bottom: 12px;">
                <!-- Fila 1 de ingrediente -->
                <div class="fila-ingrediente-admin" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                    <select name="ingredientes[]" style="flex-grow: 1; padding: 10px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-family: inherit;">
                        <option value="">-- Seleccionar Ingrediente --</option>
                        <?php 
                        $lista_ingredientes->data_seek(0);
                        while($i = $lista_ingredientes->fetch_assoc()) {
                            $stock_disp = number_format($i['stock_actual'], 2, ',', '.') . ' ' . $i['unidad_medida'];
                            echo "<option value='{$i['id']}'>" . htmlspecialchars($i['nombre']) . " ({$i['unidad_medida']}) - En bodega: $stock_disp</option>";
                        }
                        ?>
                    </select>
                    <input type="number" step="0.001" min="0.001" name="cantidades[]" placeholder="Cant. x unidad" style="width: 140px; padding: 10px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-family: inherit;">
                </div>
            </div>

            <button type="button" onclick="agregarFilaIngredienteAdmin()" class="btn btn-neutral btn-sm" style="margin-bottom: 15px;">
                <i class="fa-solid fa-plus"></i> Añadir otro ingrediente
            </button>
        </div>

        <hr style="border: 0; border-top: 1px dashed var(--rosa-fuerte); margin: 25px 0;">

        <!-- SECCIÓN 3: STOCK INICIAL -->
        <h3 style="color: var(--rosa-oscuro); border-bottom: 1.5px solid var(--rosa-suave); padding-bottom: 8px; margin-top: 0; font-size: 1.1em; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-boxes-stacked"></i> 3. Existencias Iniciales (Vitrina)
        </h3>

        <div class="form-group">
            <label>Stock Inicial a Hornear / Poner en Vitrina:</label>
            <input type="number" name="stock" value="<?php echo htmlspecialchars($_POST['stock'] ?? '0'); ?>" min="0" required style="max-width: 150px; font-weight: bold; font-size: 1.1em;">
            <small style="color: var(--texto-mutado); display: block; margin-top: 6px; line-height: 1.45;">
                • Si dejas <strong>0</strong>: El producto se registrará en el catálogo con su receta (sin existencias iniciales). Podrás fabricarlo después en Inventario.<br>
                • Si colocas una cantidad (ej: <strong>10</strong>): El sistema comprobará que existan insumos suficientes en bodega para hornear esas 10 unidades y los <strong>descontará automáticamente</strong> al guardar.
            </small>
        </div>

        <div style="margin-top: 30px;">
            <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.05em; padding: 12px 20px;">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Producto y Receta
            </button>
        </div>
    </form>
</div>

<script>
    const opcionesIngredientesAdmin = `
        <option value="">-- Seleccionar Ingrediente --</option>
        <?php 
        $lista_ingredientes->data_seek(0);
        while($i = $lista_ingredientes->fetch_assoc()) {
            $stock_disp = number_format($i['stock_actual'], 2, ',', '.') . ' ' . $i['unidad_medida'];
            echo "<option value='{$i['id']}'>" . htmlspecialchars($i['nombre'], ENT_QUOTES) . " ({$i['unidad_medida']}) - En bodega: $stock_disp</option>";
        }
        ?>
    `;

    function toggleSeccionReceta() {
        const chk = document.getElementById('toggleReceta');
        const cont = document.getElementById('seccionRecetaContenedor');
        cont.style.display = chk.checked ? 'block' : 'none';
    }

    function agregarFilaIngredienteAdmin() {
        const contenedor = document.getElementById('contenedorIngredientesReceta');
        const fila = document.createElement('div');
        fila.className = 'fila-ingrediente-admin';
        fila.style = 'display: flex; gap: 10px; margin-bottom: 10px; align-items: center;';
        
        const select = document.createElement('select');
        select.name = 'ingredientes[]';
        select.style = 'flex-grow: 1; padding: 10px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-family: inherit;';
        select.innerHTML = opcionesIngredientesAdmin;
        select.required = true;
        
        const input = document.createElement('input');
        input.type = 'number';
        input.step = '0.001';
        input.min = '0.001';
        input.name = 'cantidades[]';
        input.placeholder = 'Cant. x unidad';
        input.style = 'width: 140px; padding: 10px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-family: inherit;';
        input.required = true;
        
        const btnEliminar = document.createElement('button');
        btnEliminar.type = 'button';
        btnEliminar.className = 'btn btn-danger btn-icon';
        btnEliminar.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
        btnEliminar.title = 'Quitar fila';
        btnEliminar.onclick = function() { fila.remove(); };
        
        fila.appendChild(select);
        fila.appendChild(input);
        fila.appendChild(btnEliminar);
        contenedor.appendChild(fila);
    }
</script>

<?php require 'includes/footer.php'; ?>
