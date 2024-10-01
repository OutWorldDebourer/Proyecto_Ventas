<?php
// historial.php

// Configuración de errores para registro
ini_set('display_errors', 0); // Ocultar errores en pantalla
ini_set('log_errors', 1);     // Habilitar registro de errores
ini_set('error_log', __DIR__ . '/error_log.txt'); // Archivo de log
error_reporting(E_ALL);

// Incluir la conexión a la base de datos
require 'db.php';

// Verificar que $conn está definido
if (!isset($conn)) {
    die("La conexión a la base de datos no está definida.");
}

// Manejar la lógica de filtrado y búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_usuario = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';
$filter_fecha = isset($_GET['fecha']) ? trim($_GET['fecha']) : '';
$filter_tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';

// Construir la consulta con filtros para ambos historiales
$query = "
    SELECT 
        'Inventario' AS tipo,
        h.id AS historial_id, 
        h.producto_id, 
        p.nombre AS producto_nombre, 
        h.accion, 
        h.usuario, 
        h.fecha 
    FROM 
        historial_inventario h
    JOIN 
        productos p ON h.producto_id = p.id
    WHERE 
        1
    ";

$params = [];
$types = "";

// Aplicar filtros para historial_inventario
if ($search !== '') {
    $query .= " AND p.nombre LIKE ?";
    $params[] = '%' . $search . '%';
    $types .= "s";
}

if ($filter_usuario !== '') {
    $query .= " AND h.usuario = ?";
    $params[] = $filter_usuario;
    $types .= "s";
}

if ($filter_fecha !== '') {
    $query .= " AND DATE(h.fecha) = ?";
    $params[] = $filter_fecha;
    $types .= "s";
}

if ($filter_tipo !== '') {
    $query .= " AND 'Inventario' = ?";
    $params[] = $filter_tipo;
    $types .= "s";
}

$query .= " 

    UNION ALL

    SELECT 
        'Ventas' AS tipo,
        h.id AS historial_id, 
        h.venta_id AS producto_id, 
        v.tipo_documento AS producto_nombre, 
        h.accion, 
        h.usuario, 
        h.fecha 
    FROM 
        historial h
    JOIN 
        ventas v ON h.venta_id = v.id
    WHERE 
        1
    ";

// Aplicar los mismos filtros para historial de ventas
if ($search !== '') {
    $query .= " AND v.tipo_documento LIKE ?";
    $params[] = '%' . $search . '%';
    $types .= "s";
}

if ($filter_usuario !== '') {
    $query .= " AND h.usuario = ?";
    $params[] = $filter_usuario;
    $types .= "s";
}

if ($filter_fecha !== '') {
    $query .= " AND DATE(h.fecha) = ?";
    $params[] = $filter_fecha;
    $types .= "s";
}

if ($filter_tipo !== '') {
    $query .= " AND 'Ventas' = ?";
    $params[] = $filter_tipo;
    $types .= "s";
}

$query .= " ORDER BY fecha DESC";

// Preparar la consulta
$stmt = $conn->prepare($query);
if ($stmt === false) {
    error_log("Error en la preparación de la consulta: " . $conn->error);
    die("Error interno al cargar el historial.");
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
    error_log("Error al ejecutar la consulta: " . $stmt->error);
    die("Error interno al cargar el historial.");
}

$result = $stmt->get_result();
if ($result === false) {
    error_log("Error al obtener los resultados: " . $stmt->error);
    die("Error interno al cargar el historial.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Cambios</title>
    <!-- Bootstrap CSS -->
    <link href="./css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estilos adicionales para mejorar la presentación */
        body {
            background-color: #f8f9fa;
        }

        h1 {
            color: #343a40;
        }

        .filter-container {
            margin-bottom: 20px;
        }

        .filter-container .form-control, .filter-container .form-select {
            max-width: 300px;
            display: inline-block;
            margin-right: 10px;
        }

        @media (max-width: 576px) {
            .filter-container .form-control, .filter-container .form-select {
                width: 100%;
                margin-bottom: 10px;
            }
        }

        table {
            background-color: #ffffff;
        }

        .export-button {
            margin-bottom: 20px;
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
                    <li class="nav-item"><a class="nav-link active" href="historial.php">Historial</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h1>Historial de Cambios</h1>

        <!-- Botón para Exportar Historial a CSV -->
        <div class="clearfix">
            <a href="export.php?type=historial" class="btn btn-outline-primary export-button">Exportar Historial a CSV</a>
        </div>

        <!-- Formulario de Filtros -->
        <form method="GET" action="historial.php" class="filter-container">
            <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o tipo de documento..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="usuario" class="form-select">
                <option value="">Todos los Usuarios</option>
                <option value="Administrador" <?php if ($filter_usuario === 'Administrador') echo 'selected'; ?>>Administrador</option>
                <!-- Añadir más opciones de usuarios si es necesario -->
            </select>
            <select name="tipo" class="form-select">
                <option value="">Todos los Tipos</option>
                <option value="Inventario" <?php if ($filter_tipo === 'Inventario') echo 'selected'; ?>>Inventario</option>
                <option value="Ventas" <?php if ($filter_tipo === 'Ventas') echo 'selected'; ?>>Ventas</option>
            </select>
            <input type="date" name="fecha" class="form-control" value="<?php echo htmlspecialchars($filter_fecha); ?>">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="historial.php" class="btn btn-secondary">Limpiar Filtros</a>
        </form>

        <!-- Listado del Historial -->
        <table class="table table-striped table-bordered" id="historialTable">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>ID Producto/Venta</th>
                    <th>Nombre Producto/Tipo Documento</th>
                    <th>Acción</th>
                    <th>Usuario</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['historial_id'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['tipo'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['producto_id'] ?? ''); ?></td>
                            <td>
                                <?php 
                                    if ($row['tipo'] === 'Inventario') {
                                        echo htmlspecialchars($row['producto_nombre'] ?? '');
                                    } elseif ($row['tipo'] === 'Ventas') {
                                        echo htmlspecialchars($row['producto_nombre'] ?? '');
                                    } else {
                                        echo 'N/A';
                                    }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['accion'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['usuario'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($row['fecha']))); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No hay registros en el historial.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS (Necesario para los componentes interactivos) -->
    <script src="./js/bootstrap.bundle.min.js"></script>
</body>
</html>
