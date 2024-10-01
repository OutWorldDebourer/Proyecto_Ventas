<?php
// ventas.php

// Configuración de errores
ini_set('display_errors', 0); // Ocultar errores en pantalla
ini_set('log_errors', 1);     // Habilitar registro de errores
ini_set('error_log', __DIR__ . '/error_log.txt'); // Archivo de log
error_reporting(E_ALL);

require 'db.php';

// Inicializar contadores de boletas y facturas
$contador_boletas = contarDocumentos($conn, 'Boleta');
$contador_facturas = contarDocumentos($conn, 'Factura');

// Manejar venta_id desde GET si está presente
$venta_id = isset($_GET['venta_id']) ? intval($_GET['venta_id']) : null;

// Manejar la lógica de inserción de ventas y anulación
$venta_registrada = false;
$anulada = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'anular') {
        // Procesar la anulación
        $venta_id_to_anular = isset($_POST['venta_id']) ? intval($_POST['venta_id']) : null;
        if ($venta_id_to_anular) {
            // Lógica para anular la venta
            $resultado = anularVenta($conn, $venta_id_to_anular);
            if ($resultado['anulada']) {
                header("Location: ventas.php?anulado=1&venta_id=" . $venta_id_to_anular);
                exit();
            } else {
                $errors = $resultado['errors'];
            }
        } else {
            $errors[] = 'ID de venta inválido para anular.';
        }
    } else {
        // Procesar la venta
        $resultado = procesarVenta($conn);
        $venta_registrada = $resultado['venta_registrada'];
        $venta_id_post = $resultado['venta_id'];
        $errors = $resultado['errors'];

        if ($venta_registrada && empty($errors)) {
            header("Location: ventas.php?success=1&venta_id=" . $venta_id_post);
            exit();
        }
    }
}

// Obtener todos los productos para los ítems
$productos = obtenerProductos($conn);

// Preparar opciones de productos para JavaScript
$productos_json = json_encode($productos);

// Manejar la paginación del historial
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Obtener el historial de acciones con paginación
$historial_entries = obtenerHistorial($conn, $limit, $offset);

// Obtener el total de entradas en el historial para calcular el total de páginas
$total_historial = contarHistorial($conn);
$total_pages = ceil($total_historial / $limit);

/**
 * Anular una venta.
 */
function anularVenta($conn, $venta_id) {
    $errors = [];
    $anulada = false;

    // Verificar que la venta existe y está activa
    $stmt = $conn->prepare("SELECT estado FROM ventas WHERE id = ?");
    $stmt->bind_param("i", $venta_id);
    if ($stmt->execute()) {
        $stmt->bind_result($estado);
        if ($stmt->fetch()) {
            if ($estado !== 'activo') {
                $errors[] = 'La venta ya está anulada o no se puede anular.';
            }
        } else {
            $errors[] = 'Venta no encontrada.';
        }
    } else {
        $errors[] = 'Error al verificar la venta: ' . $stmt->error;
    }
    $stmt->close();

    if (empty($errors)) {
        // Iniciar transacción
        $conn->begin_transaction();

        try {
            // Actualizar el estado de la venta a 'anulado'
            $stmt_update = $conn->prepare("UPDATE ventas SET estado = 'anulado' WHERE id = ?");
            $stmt_update->bind_param("i", $venta_id);
            $stmt_update->execute();
            if ($stmt_update->affected_rows === 0) {
                throw new Exception('No se pudo actualizar el estado de la venta.');
            }
            $stmt_update->close();

            // Revertir el stock de los productos
            $stmt_items = $conn->prepare("SELECT producto_id, cantidad FROM venta_items WHERE venta_id = ?");
            $stmt_items->bind_param("i", $venta_id);
            if ($stmt_items->execute()) {
                $result_items = $stmt_items->get_result();
                while ($item = $result_items->fetch_assoc()) {
                    $producto_id = $item['producto_id'];
                    $cantidad = $item['cantidad'];

                    // Actualizar el stock
                    $stmt_stock = $conn->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
                    $stmt_stock->bind_param("ii", $cantidad, $producto_id);
                    $stmt_stock->execute();
                    if ($stmt_stock->affected_rows === 0) {
                        throw new Exception('No se pudo actualizar el stock del producto ID ' . $producto_id);
                    }
                    $stmt_stock->close();
                }
            } else {
                throw new Exception('Error al obtener los ítems de la venta: ' . $stmt_items->error);
            }
            $stmt_items->close();

            // Registrar en el historial
            $accion = 'Anulada venta ID ' . $venta_id;
            $usuario = 'Administrador'; // En un entorno real, obtener del sistema de autenticación
            $stmt_historial = $conn->prepare("INSERT INTO historial (venta_id, accion, usuario) VALUES (?, ?, ?)");
            $stmt_historial->bind_param("iss", $venta_id, $accion, $usuario);
            $stmt_historial->execute();
            $stmt_historial->close();

            // Confirmar transacción
            $conn->commit();
            $anulada = true;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error al anular la venta: " . $e->getMessage());
            $errors[] = 'Error interno al anular la venta.';
        }
    }

    return ['anulada' => $anulada, 'errors' => $errors];
}

/**
 * Contar documentos de un tipo específico.
 */
function contarDocumentos($conn, $tipo_documento) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ventas WHERE tipo_documento = ?");
    $stmt->bind_param("s", $tipo_documento);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $contador = $row['total'] + 1;
    } else {
        error_log("Error al contar documentos: " . $stmt->error);
        $contador = 1;
    }
    $stmt->close();
    return $contador;
}

/**
 * Obtener todos los productos con stock disponible.
 */
function obtenerProductos($conn) {
    $productos = [];
    $result = $conn->query("SELECT id, nombre, stock FROM productos WHERE stock > 0");
    if ($result) {
        while ($producto = $result->fetch_assoc()) {
            $productos[] = $producto;
        }
    } else {
        error_log("Error al obtener productos: " . $conn->error);
    }
    return $productos;
}

/**
 * Procesar la venta.
 */
function procesarVenta($conn) {
    $venta_registrada = false;
    $venta_id = null;
    $errors = [];

    // Recoger y sanitizar los datos del formulario
    $denominacion = isset($_POST['denominacion']) ? trim($_POST['denominacion']) : null;
    $tipo_documento = $_POST['tipo_documento'] ?? null;
    $moneda = $_POST['moneda'] ?? null;
    $tipo_cambio = isset($_POST['tipo_cambio']) ? floatval($_POST['tipo_cambio']) : 1;
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : null;
    $igv = isset($_POST['igv']) ? floatval($_POST['igv']) : 18;

    // Validaciones
    if ($tipo_documento === 'Factura' && empty($denominacion)) {
        $errors[] = 'La denominación es obligatoria para facturas.';
    }
    if ($tipo_cambio <= 0) {
        $errors[] = 'El tipo de cambio debe ser un valor positivo.';
    }
    if ($igv < 0) {
        $errors[] = 'El IGV no puede ser negativo.';
    }

    // Validar y procesar los ítems
    $items = $_POST['items'] ?? [];
    if (empty($items)) {
        $errors[] = 'Debe agregar al menos un ítem a la venta.';
    }

    $subtotal_sin_igv = 0;
    foreach ($items as $item) {
        $cantidad = isset($item['cantidad']) ? intval($item['cantidad']) : 0;
        $precio_unitario = isset($item['precio_unitario']) ? floatval($item['precio_unitario']) : 0;
        if ($cantidad <= 0 || $precio_unitario < 0) {
            $errors[] = 'Las cantidades deben ser positivas y los precios unitarios no pueden ser negativos.';
            break;
        }
        // Calcular el subtotal sin IGV para cada ítem
        $subtotal_sin_igv += ($cantidad * $precio_unitario) / (1 + ($igv / 100));
    }

    // Calcular IGV y Total
    $total_igv = ($subtotal_sin_igv * $igv) / 100;
    $total = $subtotal_sin_igv + $total_igv;

    // Verificar si la fecha ha sido modificada
    $fecha_hoy = date('Y-m-d');
    if ($fecha !== $fecha_hoy) {
        $modificacion_fecha = "Fecha modificada de $fecha_hoy a $fecha.";
        $observaciones = $observaciones ? $observaciones . " " . $modificacion_fecha : $modificacion_fecha;
    }

    if (empty($errors)) {
        // Iniciar transacción
        $conn->begin_transaction();

        try {
            // Insertar la venta
            $stmt = $conn->prepare("INSERT INTO ventas (denominacion, tipo_documento, moneda, tipo_cambio, fecha, subtotal, igv, total, observaciones, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')");

            $stmt->bind_param("sssdsssss", $denominacion, $tipo_documento, $moneda, $tipo_cambio, $fecha, $subtotal_sin_igv, $total_igv, $total, $observaciones);
            $stmt->execute();
            $venta_id = $stmt->insert_id;
            $stmt->close();

            // Insertar los ítems y actualizar stock
            foreach ($items as $item) {
                $producto_id = isset($item['producto_id']) ? intval($item['producto_id']) : 0;
                $cantidad = isset($item['cantidad']) ? intval($item['cantidad']) : 0;
                $precio_unitario = isset($item['precio_unitario']) ? floatval($item['precio_unitario']) : 0;

                // Insertar ítem
                $stmt_item = $conn->prepare("INSERT INTO venta_items (venta_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
                $stmt_item->bind_param("iiid", $venta_id, $producto_id, $cantidad, $precio_unitario);
                $stmt_item->execute();
                $stmt_item->close();

                // Actualizar stock
                $stmt_stock = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $stmt_stock->bind_param("iii", $cantidad, $producto_id, $cantidad);
                $stmt_stock->execute();
                if ($stmt_stock->affected_rows === 0) {
                    throw new Exception('Stock insuficiente para el producto ID ' . $producto_id);
                }
                $stmt_stock->close();
            }

            // Registrar en el historial
            $accion = 'Registrada venta ID ' . $venta_id;
            $usuario = 'Administrador'; // En un entorno real, obtener del sistema de autenticación
            $stmt_historial = $conn->prepare("INSERT INTO historial (venta_id, accion, usuario) VALUES (?, ?, ?)");
            $stmt_historial->bind_param("iss", $venta_id, $accion, $usuario);
            $stmt_historial->execute();
            $stmt_historial->close();

            // Confirmar transacción
            $conn->commit();
            $venta_registrada = true;

        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error al procesar la venta: " . $e->getMessage());
            $errors[] = 'Error interno al procesar la venta.';
        }
    }

    return ['venta_registrada' => $venta_registrada, 'venta_id' => $venta_id, 'errors' => $errors];
}

/**
 * Obtener el historial de acciones con paginación.
 */
function obtenerHistorial($conn, $limit, $offset) {
    $historial = [];
    // Incluye 'v.estado' en la selección
    $stmt = $conn->prepare("SELECT h.fecha, v.id AS venta_id, v.tipo_documento, v.total, v.estado 
                            FROM historial h 
                            LEFT JOIN ventas v ON h.venta_id = v.id 
                            ORDER BY h.fecha DESC 
                            LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($entry = $result->fetch_assoc()) {
            $historial[] = $entry;
        }
    } else {
        error_log("Error al obtener el historial: " . $stmt->error);
    }
    $stmt->close();
    return $historial;
}

/**
 * Contar el total de entradas en el historial.
 */
function contarHistorial($conn) {
    $total = 0;
    $result = $conn->query("SELECT COUNT(*) as total FROM historial");
    if ($result) {
        $row = $result->fetch_assoc();
        $total = $row['total'];
    } else {
        error_log("Error al contar el historial: " . $conn->error);
    }
    return $total;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Venta</title>
    <!-- Bootstrap CSS -->
    <link href="./css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="./js/chart.js"></script>
    <link rel="stylesheet" href="./css/styles.css">
    <!-- Estilos adicionales -->
    <style>
        .print-section {
            display: none;
            margin-top: 50px;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .print-section, .print-section * {
                visibility: visible;
            }
            .print-section {
                position: absolute;
                left: 0;
                top: 0;
            }
        }
    </style>
    <script>
        // Datos de productos en formato JSON
        const productos = <?php echo $productos_json; ?>;

        // Funciones globales
        function addItem() {
            const itemsDiv = document.getElementById('items');
            const itemIndex = document.querySelectorAll('.item-row').length;
            const newItem = document.createElement('div');
            newItem.classList.add('row', 'mb-3', 'item-row');

            // Crear opciones de productos
            let productoOptions = '<option value="">Selecciona un producto</option>';
            productos.forEach(producto => {
                if (producto.stock > 0) {
                    productoOptions += `<option value="${producto.id}">${producto.nombre} (Stock: ${producto.stock})</option>`;
                }
            });

            newItem.innerHTML = `
                <div class="col-md-4">
                    <label>Producto/Servicio:</label>
                    <select name="items[${itemIndex}][producto_id]" class="form-select" required>
                        ${productoOptions}
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Cantidad:</label>
                    <input type="number" name="items[${itemIndex}][cantidad]" class="form-control cantidad" min="1" required>
                </div>
                <div class="col-md-3">
                    <label>Precio Unitario:</label>
                    <input type="number" step="0.01" name="items[${itemIndex}][precio_unitario]" class="form-control precio_unitario" min="0" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-danger" onclick="removeItem(this)">Eliminar Ítem</button>
                </div>
            `;
            itemsDiv.appendChild(newItem);
            calculateTotals();
        }

        function removeItem(button) {
            button.closest('.item-row').remove();
            calculateTotals();
        }

        function calculateTotals() {
            const itemRows = document.querySelectorAll('.item-row');
            let subtotalSinIGV = 0;
            let igv = parseFloat(document.getElementById('igv').value) || 0;

            itemRows.forEach(item => {
                const cantidad = parseInt(item.querySelector('input[name*="[cantidad]"]').value) || 0;
                const precio = parseFloat(item.querySelector('input[name*="[precio_unitario]"]').value) || 0;
                if (1 + (igv / 100) !== 0) {
                    subtotalSinIGV += (cantidad * precio) / (1 + (igv / 100));
                }
            });

            document.getElementById('subtotal_sin_igv').value = subtotalSinIGV.toFixed(2);
            const totalIGV = (subtotalSinIGV * igv) / 100;
            document.getElementById('total').value = (subtotalSinIGV + totalIGV).toFixed(2);
        }

        function generarComprobante() {
            window.print();
        }

        function generarOtraVenta() {
            window.location.href = 'ventas.php';
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Establecer la fecha por defecto a hoy
            const fechaInput = document.getElementById('fecha');
            const hoy = new Date().toISOString().split('T')[0];
            fechaInput.value = hoy;

            // Establecer el tipo de documento por defecto a Boleta
            const tipoDocumento = document.getElementById('tipo_documento');
            tipoDocumento.value = 'Boleta';

            // Manejar la visibilidad del campo Denominación
            const denominacionDiv = document.getElementById('denominacion_div');
            const denominacionInput = document.getElementById('denominacion');
            const tipoDocumentoSelect = document.getElementById('tipo_documento');

            function toggleDenominacion() {
                if (tipoDocumentoSelect.value === 'Factura') {
                    denominacionDiv.style.display = 'block';
                    denominacionInput.required = true;
                } else {
                    denominacionDiv.style.display = 'none';
                    denominacionInput.required = false;
                    denominacionInput.value = '';
                }
            }

            tipoDocumentoSelect.addEventListener('change', toggleDenominacion);
            toggleDenominacion(); // Inicializar al cargar

            // Manejar el tipo de cambio según la moneda seleccionada
            const monedaSelect = document.getElementById('moneda');
            const tipoCambioInput = document.getElementById('tipo_cambio');

            function setTipoCambio() {
                if (monedaSelect.value === 'PEN') {
                    tipoCambioInput.value = '1';
                    tipoCambioInput.readOnly = true;
                } else {
                    tipoCambioInput.readOnly = false;
                    tipoCambioInput.value = '';
                }
                calculateTotals();
            }

            monedaSelect.addEventListener('change', setTipoCambio);
            setTipoCambio(); // Inicializar al cargar

            // Eventos para recalcular al cambiar IGV
            document.getElementById('igv').addEventListener('input', calculateTotals);

            // Evento para recalcular al cambiar cantidad o precio
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('cantidad') || e.target.classList.contains('precio_unitario')) {
                    calculateTotals();
                }
            });

            // Inicializar cálculos al cargar
            calculateTotals();
        });
    </script>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Gestión de Ventas</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="ventas.php">Ventas</a></li>
                <li class="nav-item"><a class="nav-link" href="almacen.php">Almacén</a></li>
                <li class="nav-item"><a class="nav-link" href="catalogo.php">Catálogo</a></li>
                <li class="nav-item"><a class="nav-link" href="historial.php">Historial</a></li>
            </ul>
        </div>
    </div>
</nav>



    <div class="container mt-5">
        <h1 class="mb-4">Registrar Venta</h1>

        <!-- Contadores de Boletas y Facturas -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-header">Boletas Emitidas</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($contador_boletas); ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-white bg-success mb-3">
                    <div class="card-header">Facturas Emitidas</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($contador_facturas); ?></h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensajes de Éxito -->
        <?php if (isset($_GET['success']) && isset($_GET['venta_id'])): ?>
            <div class="alert alert-success">
                Venta registrada correctamente.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['anulado']) && isset($_GET['venta_id'])): ?>
            <div class="alert alert-warning">
                Venta anulada correctamente.
            </div>
        <?php endif; ?>

        <!-- Mensajes de Error -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Formulario de Venta -->
        <?php if (!isset($_GET['success'])): ?>
            <form action="ventas.php" method="POST">
                <div class="mb-3 row">
                    <label for="tipo_documento" class="col-sm-2 col-form-label">Tipo de Documento:</label>
                    <div class="col-sm-10">
                        <select id="tipo_documento" name="tipo_documento" class="form-select" required>
                            <option value="Boleta">Boleta</option>
                            <option value="Factura">Factura</option>
                        </select>
                    </div>
                </div>

                <div id="denominacion_div" class="mb-3 row" style="display: none;">
                    <label for="denominacion" class="col-sm-2 col-form-label">Denominación:</label>
                    <div class="col-sm-10">
                        <input type="text" id="denominacion" name="denominacion" class="form-control">
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="moneda" class="col-sm-2 col-form-label">Moneda:</label>
                    <div class="col-sm-10">
                        <select id="moneda" name="moneda" class="form-select" required>
                            <option value="PEN">Soles (PEN)</option>
                            <option value="USD">Dólares (USD)</option>
                            <option value="EUR">Euros (EUR)</option>
                            <!-- Agregar más divisas según sea necesario -->
                        </select>
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="tipo_cambio" class="col-sm-2 col-form-label">Tipo de Cambio:</label>
                    <div class="col-sm-10">
                        <input type="number" step="0.0001" id="tipo_cambio" name="tipo_cambio" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="igv" class="col-sm-2 col-form-label">IGV (%):</label>
                    <div class="col-sm-10">
                        <input type="number" step="0.01" id="igv" name="igv" class="form-control" value="18" min="0" required>
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="fecha" class="col-sm-2 col-form-label">Fecha:</label>
                    <div class="col-sm-10">
                        <input type="date" id="fecha" name="fecha" class="form-control" required>
                    </div>
                </div>

                <h2 class="mt-4">Ítems de la Venta</h2>
                <div id="items">
                    <div class="row mb-3 item-row">
                        <div class="col-md-4">
                            <label>Producto/Servicio:</label>
                            <select name="items[0][producto_id]" class="form-select" required>
                                <option value="">Selecciona un producto</option>
                                <?php foreach ($productos as $producto): ?>
                                    <?php if ($producto['stock'] > 0): ?>
                                        <option value="<?php echo htmlspecialchars($producto['id']); ?>">
                                            <?php echo htmlspecialchars($producto['nombre']); ?> (Stock: <?php echo htmlspecialchars($producto['stock']); ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Cantidad:</label>
                            <input type="number" name="items[0][cantidad]" class="form-control cantidad" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label>Precio Unitario:</label>
                            <input type="number" step="0.01" name="items[0][precio_unitario]" class="form-control precio_unitario" min="0" required>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-danger" onclick="removeItem(this)">Eliminar Ítem</button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary mb-3" onclick="addItem()">Agregar Ítem</button>

                <div class="mb-3 row">
                    <label for="subtotal_sin_igv" class="col-sm-2 col-form-label">Subtotal sin IGV:</label>
                    <div class="col-sm-10">
                        <input type="text" id="subtotal_sin_igv" name="subtotal_sin_igv" class="form-control" readonly>
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="total" class="col-sm-2 col-form-label">Total:</label>
                    <div class="col-sm-10">
                        <input type="text" id="total" name="total" class="form-control" readonly>
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="observaciones" class="col-sm-2 col-form-label">Observaciones:</label>
                    <div class="col-sm-10">
                        <textarea id="observaciones" name="observaciones" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">Registrar Venta</button>
            </form>
        <?php else: ?>
            <!-- Botón para generar otra venta -->
            <button type="button" class="btn btn-primary" onclick="generarOtraVenta()">Generar Otra Venta</button>

            <!-- Botón para generar comprobante -->
            <button type="button" class="btn btn-secondary" onclick="generarComprobante()">Generar Comprobante</button>

            <!-- Sección para imprimir la boleta -->
            <?php
            if ($venta_id !== null) {
                // Obtener detalles de la venta
                $stmt_venta = $conn->prepare("SELECT tipo_documento, denominacion, moneda, tipo_cambio, fecha, igv, subtotal, total, observaciones FROM ventas WHERE id = ?");
                $stmt_venta->bind_param("i", $venta_id);
                if ($stmt_venta->execute()) {
                    $stmt_venta->bind_result($tipo_documento, $denominacion, $moneda, $tipo_cambio, $fecha, $igv, $subtotal_sin_igv_db, $total_db, $observaciones_db);
                    $stmt_venta->fetch();
                    $stmt_venta->close();
                }

                // Obtener ítems de la venta
                $stmt_items = $conn->prepare("SELECT p.nombre, vi.cantidad, vi.precio_unitario FROM venta_items vi JOIN productos p ON vi.producto_id = p.id WHERE vi.venta_id = ?");
                $stmt_items->bind_param("i", $venta_id);
                if ($stmt_items->execute()) {
                    $result_items = $stmt_items->get_result();
                    $stmt_items->close();
                }
            }
            ?>

            <?php if (isset($tipo_documento)): ?>
                <div class="print-section">
                    <h2>Comprobante de Venta</h2>
                    <p><strong>Tipo de Documento:</strong> <?php echo htmlspecialchars($tipo_documento ?? ''); ?></p>
                    <p><strong>Denominación:</strong> <?php echo htmlspecialchars($denominacion ?? 'N/A'); ?></p>
                    <p><strong>Moneda:</strong> <?php echo htmlspecialchars($moneda ?? ''); ?></p>
                    <p><strong>Tipo de Cambio:</strong> <?php echo htmlspecialchars($tipo_cambio ?? ''); ?></p>
                    <p><strong>Fecha:</strong> <?php echo htmlspecialchars($fecha ?? ''); ?></p>
                    <p><strong>IGV:</strong> <?php echo htmlspecialchars($igv ?? ''); ?>%</p>
                    <h3>Ítems:</h3>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Producto/Servicio</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($result_items)): ?>
                                <?php while ($item = $result_items->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['nombre'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($item['cantidad'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars(number_format($item['precio_unitario'] ?? 0, 2)); ?></td>
                                        <td><?php echo htmlspecialchars(number_format(($item['cantidad'] ?? 0) * ($item['precio_unitario'] ?? 0), 2)); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">No hay ítems para mostrar.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <p><strong>Subtotal sin IGV:</strong> <?php echo htmlspecialchars(number_format($subtotal_sin_igv_db ?? 0, 2)); ?> <?php echo htmlspecialchars($moneda ?? ''); ?></p>
                    <p><strong>Total IGV:</strong> <?php echo htmlspecialchars(number_format(($subtotal_sin_igv_db ?? 0) * ($igv ?? 0) / 100, 2)); ?> <?php echo htmlspecialchars($moneda ?? ''); ?></p>
                    <p><strong>Total:</strong> <?php echo htmlspecialchars(number_format($total_db ?? 0, 2)); ?> <?php echo htmlspecialchars($moneda ?? ''); ?></p>
                    <p><strong>Observaciones:</strong> <?php echo htmlspecialchars($observaciones_db ?? 'N/A'); ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
<!-- Botón de Exportar Ventas -->

        <!-- Historial de Acciones -->
        <h2 class="mt-5">Historial de Acciones</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Número</th>
                    <th>Tipo de Documento</th>
                    <th>Total</th>
                    <th>Anular</th>
                    <th>Detalle</th>
                    <th>Imprimir</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($historial_entries)): ?>
                    <?php foreach ($historial_entries as $entry): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($entry['fecha']); ?></td>
                            <td><?php echo htmlspecialchars($entry['venta_id']); ?></td>
                            <td><?php echo htmlspecialchars($entry['tipo_documento'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars(number_format($entry['total'], 2)); ?></td>
                            <td>
                                <?php if ($entry['estado'] !== 'anulado'): ?>
                                    <form method="post" action="ventas.php" style="display:inline;">
                                        <input type="hidden" name="action" value="anular">
                                        <input type="hidden" name="venta_id" value="<?php echo htmlspecialchars($entry['venta_id']); ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas anular esta venta?');">Anular</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Anulada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="detalle_venta.php?venta_id=<?php echo htmlspecialchars($entry['venta_id']); ?>" class="btn btn-info btn-sm">Detalle</a>
                            </td>
                            <td>
                                <a href="imprimir_venta.php?venta_id=<?php echo htmlspecialchars($entry['venta_id']); ?>" class="btn btn-secondary btn-sm">Imprimir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No hay acciones registradas en el historial.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Paginación del Historial -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Paginación del historial">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" tabindex="-1">Anterior</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS (Opcional, para funcionalidades avanzadas) -->
    <script src="./js/bootstrap.bundle.min.js"></script>
</body>
</html>
