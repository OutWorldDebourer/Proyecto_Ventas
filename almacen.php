<?php 
// almacen.php

// Configuración de errores para registro
ini_set('display_errors', 0); // Ocultar errores en pantalla
ini_set('log_errors', 1);     // Habilitar registro de errores
ini_set('error_log', __DIR__ . '/error_log.txt'); // Archivo de log
error_reporting(E_ALL);

// Iniciar la sesión para manejar tokens CSRF
session_start();

// Incluir la conexión a la base de datos
require 'db.php';

// Verificar que $conn está definido
if (!isset($conn)) {
    die("La conexión a la base de datos no está definida.");
}

// Generar un token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inicializar errores y mensajes de éxito
$errors = [];
$success = '';

// Manejar la lógica de registrar compras
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comprar'])) {
    // Verificar el token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Token CSRF inválido.";
    }

    // Obtener y sanitizar los datos del formulario
    $producto_id = isset($_POST['producto_id']) ? intval($_POST['producto_id']) : 0;
    $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;
    $tipo_doc = isset($_POST['tipo_doc']) ? trim($_POST['tipo_doc']) : '';
    $numero_doc = isset($_POST['numero_doc']) ? trim($_POST['numero_doc']) : null;
    $entidad = isset($_POST['entidad']) ? trim($_POST['entidad']) : null;
    $direccion_fiscal = isset($_POST['direccion_fiscal']) ? trim($_POST['direccion_fiscal']) : null;
    $telefonos = isset($_POST['telefonos']) ? $_POST['telefonos'] : [];
    $emails = isset($_POST['emails']) ? $_POST['emails'] : [];
    $otros = isset($_POST['otros']) ? trim($_POST['otros']) : null;
    $monto = isset($_POST['monto']) ? floatval($_POST['monto']) : 0.00;
    $moneda = isset($_POST['moneda']) ? trim($_POST['moneda']) : 'PEN';
    $tipo_cambio = isset($_POST['tipo_cambio']) ? floatval($_POST['tipo_cambio']) : 1.0000;

    // Validaciones
    if ($producto_id <= 0) {
        $errors[] = "Producto o Servicio es requerido.";
    }

    if ($cantidad <= 0) {
        $errors[] = "La cantidad debe ser al menos 1.";
    }

    if ($monto <= 0) {
        $errors[] = "El monto debe ser un valor positivo.";
    }

    $valid_moneda = ['USD', 'EUR', 'PEN'];
    if (!in_array($moneda, $valid_moneda)) {
        $errors[] = "Moneda inválida seleccionada.";
    }

    if ($moneda !== 'PEN') {
        if ($tipo_cambio <= 0) {
            $errors[] = "El tipo de cambio debe ser un valor positivo.";
        }
    } else {
        $tipo_cambio = 1.0000;
    }

    $valid_tipo_doc = ['DNI', 'RUC', 'OTROS'];
    if (!in_array($tipo_doc, $valid_tipo_doc)) {
        $errors[] = "Tipo de documento inválido.";
    }

    if ($tipo_doc === 'RUC') {
        if (empty($numero_doc)) {
            $errors[] = "El número de RUC es requerido.";
        } elseif (!preg_match('/^\d{11}$/', $numero_doc)) {
            $errors[] = "El RUC debe tener exactamente 11 dígitos.";
        }

        if (empty($direccion_fiscal)) {
            $errors[] = "La dirección fiscal es requerida para RUC.";
        }

        if (empty($entidad)) {
            $errors[] = "La entidad es requerida para RUC.";
        }

        if (empty($telefonos)) {
            $errors[] = "Al menos un teléfono es requerido para RUC.";
        } else {
            foreach ($telefonos as $telefono) {
                $telefono = trim($telefono);
                if (empty($telefono)) {
                    $errors[] = "Los teléfonos no pueden estar vacíos.";
                    break;
                }
                // Validación de formato de teléfono (opcional)
                if (!preg_match('/^\+?\d{7,15}$/', $telefono)) {
                    $errors[] = "Formato de teléfono inválido para: " . htmlspecialchars($telefono);
                    break;
                }
            }
        }
    }

    if (!empty($emails)) {
        foreach ($emails as $email) {
            $email = trim($email);
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Uno o más emails son inválidos.";
                break;
            }
        }
    }

    if (empty($errors)) {
        // Preparar los datos para la inserción
        $telefonos_str = implode(',', array_map('trim', $telefonos));
        $emails_str = !empty($emails) ? implode(',', array_map('trim', $emails)) : null;

        // Iniciar una transacción
        $conn->begin_transaction();

        try {
            // Insertar en la tabla 'compras'
            $stmt_compra = $conn->prepare("INSERT INTO compras (producto_id, cantidad, tipo_doc, numero_doc, entidad, direccion_fiscal, telefonos, emails, otros, monto, moneda, tipo_cambio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt_compra === false) {
                throw new Exception("Error al preparar la declaración de compra: " . $conn->error);
            }

            $stmt_compra->bind_param(
                "iisssssssssd",
                $producto_id,
                $cantidad,
                $tipo_doc,
                $numero_doc,
                $entidad,
                $direccion_fiscal,
                $telefonos_str,
                $emails_str,
                $otros,
                $monto,
                $moneda,
                $tipo_cambio
            );

            if (!$stmt_compra->execute()) {
                throw new Exception("Error al ejecutar la consulta de compra: " . $stmt_compra->error);
            }

            $compra_id = $stmt_compra->insert_id;
            $stmt_compra->close();

            // Actualizar el stock en 'productos'
            $stmt_stock = $conn->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
            if ($stmt_stock === false) {
                throw new Exception("Error al preparar la consulta de actualización de stock: " . $conn->error);
            }

            $stmt_stock->bind_param("ii", $cantidad, $producto_id);

            if (!$stmt_stock->execute()) {
                throw new Exception("Error al ejecutar la consulta de actualización de stock: " . $stmt_stock->error);
            }

            $stmt_stock->close();

            // Registrar la acción en 'historial_inventario'
            $accion = 'Agregada compra ID ' . $compra_id . ' para producto ID ' . $producto_id . ' con cantidad ' . $cantidad;
            $usuario = 'Administrador'; // Obtener del sistema de autenticación real

            $stmt_historial = $conn->prepare("INSERT INTO historial_inventario (producto_id, accion, usuario) VALUES (?, ?, ?)");
            if ($stmt_historial === false) {
                throw new Exception("Error al preparar la consulta de historial: " . $conn->error);
            }

            $stmt_historial->bind_param("iss", $producto_id, $accion, $usuario);

            if (!$stmt_historial->execute()) {
                throw new Exception("Error al ejecutar la consulta de historial: " . $stmt_historial->error);
            }

            $stmt_historial->close();

            // Confirmar la transacción
            $conn->commit();

            $success = "Compra registrada exitosamente y stock actualizado.";
            $_POST = [];
        } catch (Exception $e) {
            $conn->rollback();
            error_log($e->getMessage());
            $errors[] = "Error al registrar la compra. Por favor, inténtalo de nuevo más tarde.";
        }
    }
}

// Consulta para el dropdown: Ordenar alfabéticamente por nombre
$sql_dropdown = "SELECT * FROM productos ORDER BY nombre ASC";
$result_dropdown = $conn->query($sql_dropdown);

if ($result_dropdown === false) {
    error_log("Error en la consulta del dropdown: " . $conn->error);
    die("Error interno al cargar los productos para el dropdown.");
}

// Consulta para la tabla: Incluir 'entidad', 'monto', 'moneda' y 'tipo_cambio'
$sql_table = "SELECT compras.*, productos.nombre AS producto_nombre FROM compras 
              JOIN productos ON compras.producto_id = productos.id 
              ORDER BY compras.id DESC";
$result_table = $conn->query($sql_table);

if ($result_table === false) {
    error_log("Error en la consulta de la tabla: " . $conn->error);
    die("Error interno al cargar las compras para la tabla.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Almacén - Registro de Compras</title>
    <!-- Bootstrap CSS -->
    <link href="./css/bootstrap.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="./css/select2.min.css" rel="stylesheet" />
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="./css/styles.css">
    <!-- Chart.js -->
    <script src="./js/chart.js"></script>
    <style>
        /* Estilos adicionales para mejorar la presentación */
        body {
            background-color: #f8f9fa;
        }

        h1, h2 {
            color: #343a40;
        }

        form {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        label {
            font-weight: bold;
        }

        .actions button {
            margin-right: 5px;
        }

        table {
            background-color: #ffffff;
        }

        .export-button {
            margin-bottom: 20px;
        }

        /* Ajuste para Select2 en Bootstrap */
        .select2-container .select2-selection--single {
            height: 38px; /* Igual a la altura de Bootstrap's input */
            padding: 6px 12px;
        }

        /* Mejorar la alineación vertical en la tabla */
        table th, table td {
            vertical-align: middle;
        }

        /* Ajustar el ancho de las columnas para mejor visualización */
        table th:nth-child(1),
        table td:nth-child(1) { width: 5%; }

        table th:nth-child(2),
        table td:nth-child(2) { width: 15%; }

        table th:nth-child(3),
        table td:nth-child(3) { width: 7%; }

        table th:nth-child(4),
        table td:nth-child(4) { width: 10%; }

        table th:nth-child(5),
        table td:nth-child(5) { width: 10%; }

        table th:nth-child(6),
        table td:nth-child(6) { width: 10%; }

        table th:nth-child(7),
        table td:nth-child(7) { width: 10%; }

        table th:nth-child(8),
        table td:nth-child(8) { width: 10%; }

        table th:nth-child(9),
        table td:nth-child(9) { width: 10%; }

        table th:nth-child(10),
        table td:nth-child(10) { width: 10%; }

        table th:nth-child(11),
        table td:nth-child(11) { width: 7%; }

        table th:nth-child(12),
        table td:nth-child(12) { width: 5%; }

        table th:nth-child(13),
        table td:nth-child(13) { width: 5%; }

        table th:nth-child(14),
        table td:nth-child(14) { width: 10%; }

        /* Hacer la tabla responsive */
        .table-responsive {
            overflow-x: auto;
        }
    </style>
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
        <!-- Mensajes de Éxito -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
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

        <!-- Formulario para Registrar una Compra -->
        <h2>Registrar una Nueva Compra</h2>
        <form action="almacen.php" method="POST">
            <!-- Token CSRF -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <!-- Tipo de Documento -->
            <div class="mb-3">
                <label for="tipo_doc" class="form-label">Tipo<span class="text-danger">*</span>:</label>
                <select id="tipo_doc" name="tipo_doc" class="form-select" required>
                    <option value="DNI" <?php echo (isset($_POST['tipo_doc']) && $_POST['tipo_doc'] === 'DNI') ? 'selected' : 'selected'; ?>>DNI</option>
                    <option value="RUC" <?php echo (isset($_POST['tipo_doc']) && $_POST['tipo_doc'] === 'RUC') ? 'selected' : ''; ?>>RUC</option>
                    <option value="OTROS" <?php echo (isset($_POST['tipo_doc']) && $_POST['tipo_doc'] === 'OTROS') ? 'selected' : ''; ?>>OTROS</option>
                </select>
            </div>

            <!-- Número DOC. -->
            <div class="mb-3" id="numero_doc_div">
                <label for="numero_doc" class="form-label">Número DOC.:</label>
                <input type="text" id="numero_doc" name="numero_doc" class="form-control" placeholder="Ingrese el número de documento" value="<?php echo isset($_POST['numero_doc']) ? htmlspecialchars($_POST['numero_doc']) : ''; ?>">
            </div>

            <!-- Entidad (Nuevo Campo) -->
            <div class="mb-3" id="entidad_div" style="display: none;">
                <label for="entidad" class="form-label">Entidad<span class="text-danger">*</span>:</label>
                <input type="text" id="entidad" name="entidad" class="form-control" placeholder="Ingrese la entidad" value="<?php echo isset($_POST['entidad']) ? htmlspecialchars($_POST['entidad']) : ''; ?>">
            </div>

            <!-- Dirección Fiscal -->
            <div class="mb-3" id="direccion_fiscal_div" style="display: none;">
                <label for="direccion_fiscal" class="form-label">Dirección Fiscal<span class="text-danger">*</span>:</label>
                <input type="text" id="direccion_fiscal" name="direccion_fiscal" class="form-control" placeholder="Ingrese la dirección fiscal" value="<?php echo isset($_POST['direccion_fiscal']) ? htmlspecialchars($_POST['direccion_fiscal']) : ''; ?>">
            </div>

            <!-- Producto o Servicio -->
            <div class="mb-3">
                <label for="producto_id" class="form-label">Producto o Servicio<span class="text-danger">*</span>:</label>
                <select id="producto_id" name="producto_id" class="form-select select2" required>
                    <option value="">Selecciona un producto o servicio</option>
                    <?php 
                        $selected_producto = isset($_POST['producto_id']) ? $_POST['producto_id'] : '';
                        while($producto = $result_dropdown->fetch_assoc()):
                    ?>
                        <option value="<?php echo htmlspecialchars($producto['id']); ?>" <?php echo ($producto['id'] == $selected_producto) ? 'selected' : ''; ?>>
                            <?php 
                                echo htmlspecialchars($producto['nombre']) . " (" . htmlspecialchars($producto['tipo']) . ") - Stock: " . htmlspecialchars($producto['stock']); 
                            ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Cantidad -->
            <div class="mb-3">
                <label for="cantidad" class="form-label">Cantidad<span class="text-danger">*</span>:</label>
                <input type="number" id="cantidad" name="cantidad" class="form-control" min="1" required value="<?php echo isset($_POST['cantidad']) ? htmlspecialchars($_POST['cantidad']) : ''; ?>">
            </div>

            <!-- Monto -->
            <div class="mb-3">
                <label for="monto" class="form-label">Monto<span class="text-danger">*</span>:</label>
                <input type="number" id="monto" name="monto" class="form-control" step="0.01" min="0.01" required value="<?php echo isset($_POST['monto']) ? htmlspecialchars($_POST['monto']) : ''; ?>">
            </div>

            <!-- Moneda -->
            <div class="mb-3">
                <label for="moneda" class="form-label">Moneda<span class="text-danger">*</span>:</label>
                <select id="moneda" name="moneda" class="form-select" required>
                    <option value="PEN" <?php echo (isset($_POST['moneda']) && $_POST['moneda'] === 'PEN') ? 'selected' : 'selected'; ?>>PEN</option>
                    <option value="USD" <?php echo (isset($_POST['moneda']) && $_POST['moneda'] === 'USD') ? 'selected' : ''; ?>>USD</option>
                    <option value="EUR" <?php echo (isset($_POST['moneda']) && $_POST['moneda'] === 'EUR') ? 'selected' : ''; ?>>EUR</option>
                </select>
            </div>

            <!-- Tipo de Cambio -->
            <div class="mb-3" id="tipo_cambio_div" style="display: none;">
                <label for="tipo_cambio" class="form-label">Tipo de Cambio<span class="text-danger">*</span>:</label>
                <input type="number" id="tipo_cambio" name="tipo_cambio" class="form-control" step="0.0001" min="0.0001" value="<?php echo isset($_POST['tipo_cambio']) ? htmlspecialchars($_POST['tipo_cambio']) : '1.0000'; ?>">
            </div>

            <!-- Teléfonos/Celular* (Condicional) -->
            <div class="mb-3" id="telefonos_div">
                <label class="form-label">Teléfonos/Celular<span id="telefonos_required" class="text-danger" style="display: none;">*</span>:</label>
                <div id="telefonos_container">
                    <?php
                        $telefonos_input = isset($_POST['telefonos']) && is_array($_POST['telefonos']) ? $_POST['telefonos'] : [''];
                        foreach ($telefonos_input as $index => $telefono):
                    ?>
                        <div class="input-group mb-2">
                            <input type="text" name="telefonos[]" class="form-control" placeholder="Ingrese un teléfono o celular" <?php echo (isset($_POST['tipo_doc']) && $_POST['tipo_doc'] === 'RUC') ? 'required' : ''; ?> value="<?php echo htmlspecialchars($telefono); ?>">
                            <?php if ($index === 0): ?>
                                <button type="button" class="btn btn-outline-secondary add-telefono">Agregar</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-danger remove-telefono">Eliminar</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Emails -->
            <div class="mb-3">
                <label class="form-label">Emails:</label>
                <div id="emails_container">
                    <?php
                        $emails_input = isset($_POST['emails']) && is_array($_POST['emails']) ? $_POST['emails'] : [''];
                        foreach ($emails_input as $index => $email):
                    ?>
                        <div class="input-group mb-2">
                            <input type="email" name="emails[]" class="form-control" placeholder="Ingrese un email" value="<?php echo htmlspecialchars($email); ?>">
                            <?php if ($index === 0): ?>
                                <button type="button" class="btn btn-outline-secondary add-email">Agregar</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-danger remove-email">Eliminar</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Otros -->
            <div class="mb-3">
                <label for="otros" class="form-label">Otros:</label>
                <textarea id="otros" name="otros" class="form-control" rows="3" placeholder="Ingrese comentarios adicionales"><?php echo isset($_POST['otros']) ? htmlspecialchars($_POST['otros']) : ''; ?></textarea>
            </div>

            <button type="submit" name="comprar" class="btn btn-primary">Registrar Compra</button>
        </form>

        <!-- Listado de Compras Registradas -->
        <h2 class="mt-5">Listado de Compras</h2>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Producto o Servicio</th>
                        <th>Cantidad</th>
                        <th>Tipo Documento</th>
                        <th>Número DOC.</th>
                        <th>Entidad</th>
                        <th>Dirección Fiscal</th>
                        <th>Teléfonos/Celular</th>
                        <th>Emails</th>
                        <th>Otros</th>
                        <th>Monto</th>
                        <th>Moneda</th>
                        <th>Tipo de Cambio</th>
                        <th>Fecha de Compra</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_table->num_rows > 0): ?>
                        <?php while($compra = $result_table->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($compra['id']); ?></td>
                                <td><?php echo htmlspecialchars($compra['producto_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($compra['cantidad']); ?></td>
                                <td><?php echo htmlspecialchars($compra['tipo_doc']); ?></td>
                                <td><?php echo htmlspecialchars($compra['numero_doc']); ?></td>
                                <td><?php echo htmlspecialchars($compra['entidad'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($compra['direccion_fiscal'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php 
                                        if (!empty($compra['telefonos'])) {
                                            $telefonos_array = explode(',', $compra['telefonos']);
                                            foreach ($telefonos_array as $tel) {
                                                echo htmlspecialchars($tel) . "<br>";
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        if (!empty($compra['emails'])) {
                                            $emails_array = explode(',', $compra['emails']);
                                            foreach ($emails_array as $email) {
                                                echo htmlspecialchars($email) . "<br>";
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($compra['otros'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(number_format($compra['monto'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($compra['moneda']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($compra['tipo_cambio'], 4)); ?></td>
                                <td><?php echo htmlspecialchars($compra['fecha_compra']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="14" class="text-center">No hay compras registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- jQuery (Necesario para Select2) -->
    <script src="./js/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="./js/bootstrap.bundle.min.js"></script>

    <!-- Select2 JS -->
    <script src="./js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inicializar Select2 en el elemento select con clase 'select2'
            $('.select2').select2({
                placeholder: "Selecciona un producto o servicio",
                allowClear: true
            });

            // Función para mostrar/ocultar campos basados en el tipo de documento y moneda
            $('#tipo_doc, #moneda').change(function() {
                var tipo = $('#tipo_doc').val();
                var moneda = $('#moneda').val();
                
                // Manejar campos basados en 'tipo_doc'
                if (tipo === 'RUC') {
                    $('#entidad_div').show();
                    $('#entidad').attr('required', true);
                    $('#direccion_fiscal_div').show();
                    $('#direccion_fiscal').attr('required', true);
                    $('#telefonos_required').show();
                    $('#telefonos_div input').attr('required', true);
                    
                    // Hacer que 'numero_doc' sea obligatorio para RUC
                    $('#numero_doc').attr('required', true);
                    $('#numero_doc').attr('maxlength', 11);
                    $('#numero_doc').attr('pattern', '^[0-9]{11}$');
                    $('#numero_doc').attr('title', 'El RUC debe tener exactamente 11 dígitos.');
                } else if (tipo === 'DNI') {
                    // No requerir 'Entidad' y 'Dirección Fiscal' para DNI
                    $('#entidad_div').hide();
                    $('#entidad').attr('required', false);
                    $('#direccion_fiscal_div').hide();
                    $('#direccion_fiscal').attr('required', false);
                    $('#telefonos_required').hide();
                    $('#telefonos_div input').attr('required', false);
                    
                    // Ajustar 'numero_doc' para DNI
                    $('#numero_doc').attr('required', false);
                    $('#numero_doc').attr('maxlength', 8);
                    $('#numero_doc').attr('pattern', '^[0-9]{8}$');
                    $('#numero_doc').attr('title', 'El DNI debe tener exactamente 8 dígitos.');
                } else { // OTROS
                    $('#entidad_div').hide();
                    $('#entidad').attr('required', false);
                    $('#direccion_fiscal_div').hide();
                    $('#direccion_fiscal').attr('required', false);
                    $('#telefonos_required').hide();
                    $('#telefonos_div input').attr('required', false);
                    
                    // No hacer que 'numero_doc' sea obligatorio para OTROS
                    $('#numero_doc').attr('required', false);
                    $('#numero_doc').removeAttr('maxlength pattern title');
                }

                // Manejar campos basados en 'moneda'
                if (moneda !== 'PEN' && moneda !== '') { // Mostrar 'Tipo de Cambio' si no es 'PEN'
                    $('#tipo_cambio_div').show();
                    $('#tipo_cambio').attr('required', true);
                } else {
                    $('#tipo_cambio_div').hide();
                    $('#tipo_cambio').attr('required', false);
                    $('#tipo_cambio').val('1.0000'); // Valor por defecto
                }
            });

            // Inicialmente ocultar campos basados en el valor predeterminado
            $('#tipo_doc').trigger('change');
            $('#moneda').trigger('change');

            // Función para agregar más teléfonos (hasta 5)
            var telefonoCount = $('#telefonos_container .input-group').length;
            $('.add-telefono').click(function() {
                if (telefonoCount < 5) {
                    telefonoCount++;
                    $('#telefonos_container').append(
                        '<div class="input-group mb-2">' +
                            '<input type="text" name="telefonos[]" class="form-control" placeholder="Ingrese un teléfono o celular" <?php echo (isset($_POST["tipo_doc"]) && $_POST["tipo_doc"] === "RUC") ? 'required' : ''; ?>>' +
                            '<button type="button" class="btn btn-outline-danger remove-telefono">Eliminar</button>' +
                        '</div>'
                    );
                } else {
                    alert('Máximo de 5 teléfonos.');
                }
            });

            // Eliminar teléfono
            $('#telefonos_container').on('click', '.remove-telefono', function() {
                $(this).parent().remove();
                telefonoCount--;
            });

            // Función para agregar más emails (hasta 5)
            var emailCount = $('#emails_container .input-group').length;
            $('.add-email').click(function() {
                if (emailCount < 5) {
                    emailCount++;
                    $('#emails_container').append(
                        '<div class="input-group mb-2">' +
                            '<input type="email" name="emails[]" class="form-control" placeholder="Ingrese un email">' +
                            '<button type="button" class="btn btn-outline-danger remove-email">Eliminar</button>' +
                        '</div>'
                    );
                } else {
                    alert('Máximo de 5 emails.');
                }
            });

            // Eliminar email
            $('#emails_container').on('click', '.remove-email', function() {
                $(this).parent().remove();
                emailCount--;
            });
        });
    </script>
</body>
</html>
