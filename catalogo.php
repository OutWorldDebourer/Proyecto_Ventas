<?php
// catalogo.php

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

// Función para generar códigos únicos
function generateUniqueCode($prefix = 'P') {
    return $prefix . strtoupper(substr(uniqid(), -6));
}

// Inicializar errores
$errors = [];

// Manejar la lógica de agregar y editar productos o servicios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Agregar un nuevo producto o servicio
    if (isset($_POST['agregar'])) {
        $tipo = $_POST['tipo'] ?? '';
        $nombre = trim($_POST['nombre'] ?? '');

        // Validaciones
        if (empty($nombre)) {
            $errors[] = 'El nombre del producto o servicio es obligatorio.';
        }

        if (empty($tipo) || !in_array($tipo, ['Producto', 'Servicio'])) {
            $errors[] = 'El tipo debe ser "Producto" o "Servicio".';
        }

        if (empty($errors)) {
            // Generar un código único automáticamente
            $codigo = generateUniqueCode();

            // Preparar la consulta SQL para insertar el producto/servicio
            $stmt = $conn->prepare("INSERT INTO productos (tipo, nombre, codigo, precio, stock) VALUES (?, ?, ?, 0, 0)");
            if ($stmt === false) {
                error_log("Error en la preparación de la consulta: " . $conn->error);
                $errors[] = 'Error interno. Por favor, inténtalo de nuevo más tarde.';
            } else {
                $stmt->bind_param("sss", $tipo, $nombre, $codigo);

                if ($stmt->execute()) {
                    // Registrar en el historial_inventario
                    $accion = 'Agregado producto/servicio: ' . $nombre;
                    $usuario = 'Administrador'; // En un entorno real, obtener del sistema de autenticación
                    $producto_id = $conn->insert_id;

                    $stmt_historial = $conn->prepare("INSERT INTO historial_inventario (producto_id, accion, usuario) VALUES (?, ?, ?)");
                    if ($stmt_historial === false) {
                        error_log("Error en la preparación de la consulta de historial: " . $conn->error);
                        $errors[] = 'Error interno al registrar en el historial.';
                    } else {
                        $stmt_historial->bind_param("iss", $producto_id, $accion, $usuario);

                        if (!$stmt_historial->execute()) {
                            error_log("Error al registrar en el historial: " . $stmt_historial->error);
                            $errors[] = 'Error interno al registrar en el historial.';
                        }

                        $stmt_historial->close();
                    }

                    // Redireccionar si todo fue exitoso
                    if (empty($errors)) {
                        header("Location: catalogo.php?success=1");
                        exit();
                    }
                } else {
                    error_log("Error al agregar el producto o servicio: " . $stmt->error);
                    $errors[] = 'Error al agregar el producto o servicio.';
                }

                $stmt->close();
            }
        }
    }

    // Editar el nombre de un producto o servicio existente
    if (isset($_POST['actualizar'])) {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $nombre = trim($_POST['nombre'] ?? '');

        // Validaciones
        if (empty($nombre)) {
            $errors[] = 'El nombre del producto o servicio es obligatorio.';
        }

        if ($id <= 0) {
            $errors[] = 'ID de producto inválido.';
        }

        if (empty($errors)) {
            // Obtener los valores actuales antes de la actualización
            $stmt_select = $conn->prepare("SELECT nombre FROM productos WHERE id = ?");
            if ($stmt_select === false) {
                error_log("Error en la preparación de la consulta de selección: " . $conn->error);
                $errors[] = 'Error interno. Por favor, inténtalo de nuevo más tarde.';
            } else {
                $stmt_select->bind_param("i", $id);
                if ($stmt_select->execute()) {
                    $stmt_select->bind_result($nombre_antiguo);
                    if ($stmt_select->fetch()) {
                        $stmt_select->close();

                        // Preparar la consulta SQL para actualizar solo el nombre
                        $stmt = $conn->prepare("UPDATE productos SET nombre = ? WHERE id = ?");
                        if ($stmt === false) {
                            error_log("Error en la preparación de la consulta: " . $conn->error);
                            $errors[] = 'Error interno. Por favor, inténtalo de nuevo más tarde.';
                        } else {
                            $stmt->bind_param("si", $nombre, $id);

                            if ($stmt->execute()) {
                                // Registrar en el historial_inventario con detalles anteriores y nuevos
                                $accion = 'Editado nombre del producto/servicio ID ' . $id . ' de "' . $nombre_antiguo . '" a "' . $nombre . '"';
                                $usuario = 'Administrador';

                                $stmt_historial = $conn->prepare("INSERT INTO historial_inventario (producto_id, accion, usuario) VALUES (?, ?, ?)");
                                if ($stmt_historial === false) {
                                    error_log("Error en la preparación de la consulta de historial: " . $conn->error);
                                    $errors[] = 'Error interno al registrar en el historial.';
                                } else {
                                    $stmt_historial->bind_param("iss", $id, $accion, $usuario);

                                    if (!$stmt_historial->execute()) {
                                        error_log("Error al registrar en el historial: " . $stmt_historial->error);
                                        $errors[] = 'Error interno al registrar en el historial.';
                                    }

                                    $stmt_historial->close();
                                }

                                // Redireccionar si todo fue exitoso
                                if (empty($errors)) {
                                    header("Location: catalogo.php?success=2");
                                    exit();
                                }
                            } else {
                                error_log("Error al editar el nombre del producto o servicio: " . $stmt->error);
                                $errors[] = 'Error al editar el nombre del producto o servicio.';
                            }

                            $stmt->close();
                        }
                    } else {
                        $stmt_select->close();
                        $errors[] = 'Producto o servicio no encontrado.';
                    }
                } else {
                    error_log("Error al ejecutar la consulta de selección: " . $stmt_select->error);
                    $errors[] = 'Error interno al obtener datos del producto.';
                }
            }
        }
    }
} // <-- Cierre del bloque if ($_SERVER['REQUEST_METHOD'] === 'POST')

// Obtener todos los productos y servicios
$sql = "SELECT id, tipo, nombre, codigo FROM productos";
$result = $conn->query($sql);

// Verificar si hubo error en la consulta
if ($result === false) {
    error_log("Error en la consulta de productos: " . $conn->error);
    die("Error interno al cargar los productos.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo de Productos y Servicios</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

        .actions form {
            display: inline;
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

        /* Estilos para el modal */
        .modal-header {
            background-color: #343a40;
            color: white;
        }

        /* Estilos para los filtros */
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
        <div class="clearfix">
            <h1>Catálogo de Productos y Servicios</h1>
            <!-- Botón para Exportar Catálogo a CSV -->
            <a href="export.php?type=catalogo" class="btn btn-outline-primary export-button">Exportar Catálogo a CSV</a>
        </div>

        <!-- Mensajes de Éxito -->
        <?php if (isset($_GET['success'])): ?>
            <?php if ($_GET['success'] == 1): ?>
                <div class="alert alert-success">Producto o servicio agregado correctamente.</div>
            <?php elseif ($_GET['success'] == 2): ?>
                <div class="alert alert-success">Nombre del producto o servicio editado correctamente.</div>
            <?php elseif ($_GET['success'] == 3): ?>
                <div class="alert alert-success">Producto o servicio eliminado correctamente.</div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Mensajes de Error -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error ?? 'Error desconocido.'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Formulario para Agregar Nuevo Producto o Servicio -->
        <h2>Agregar Nuevo Producto o Servicio</h2>
        <form action="catalogo.php" method="POST">
            <div class="mb-3">
                <label for="tipo" class="form-label">Tipo:</label>
                <select id="tipo" name="tipo" class="form-select" required>
                    <option value="Producto">Producto</option>
                    <option value="Servicio">Servicio</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre:</label>
                <input type="text" id="nombre" name="nombre" class="form-control" required>
            </div>

            <button type="submit" name="agregar" class="btn btn-primary">Agregar</button>
        </form>

        <!-- Filtros y Búsqueda -->
        <div class="filter-container">
            <input type="text" id="searchInput" class="form-control" placeholder="Buscar por nombre...">
            <select id="filterTipo" class="form-select">
                <option value="">Todos los Tipos</option>
                <option value="Producto">Producto</option>
                <option value="Servicio">Servicio</option>
            </select>
        </div>

        <!-- Listado de Productos y Servicios -->
        <h2>Listado de Productos y Servicios</h2>
        <table class="table table-striped table-bordered" id="productosTable">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Nombre</th>
                    <th>Código</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['tipo'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['codigo'] ?? ''); ?></td>
                            <td class="actions">
                                <!-- Botón para abrir el modal de edición -->
                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo htmlspecialchars($row['id']); ?>">
                                    Editar
                                </button>

                                <!-- Modal de Edición -->
                                <div class="modal fade" id="editModal<?php echo htmlspecialchars($row['id']); ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo htmlspecialchars($row['id']); ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="catalogo.php" method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editModalLabel<?php echo htmlspecialchars($row['id']); ?>">Editar Nombre</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                                    <div class="mb-3">
                                                        <label for="nombre_edit<?php echo htmlspecialchars($row['id']); ?>" class="form-label">Nombre:</label>
                                                        <input type="text" id="nombre_edit<?php echo htmlspecialchars($row['id']); ?>" name="nombre" class="form-control" value="<?php echo htmlspecialchars($row['nombre']); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" name="actualizar" class="btn btn-success">Guardar Cambios</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <!-- Fin del Modal -->
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No hay productos o servicios registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS (Necesario para los modales) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Script para Filtrado y Búsqueda -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const filterTipo = document.getElementById('filterTipo');
            const table = document.getElementById('productosTable');
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = tbody.getElementsByTagName('tr');

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const tipoFilter = filterTipo.value;

                for (let i = 0; i < rows.length; i++) {
                    const cells = rows[i].getElementsByTagName('td');
                    const tipo = cells[1].textContent.toLowerCase();
                    const nombre = cells[2].textContent.toLowerCase();

                    const matchesSearch = nombre.includes(searchTerm);
                    const matchesTipo = tipoFilter === '' || tipo === tipoFilter.toLowerCase();

                    if (matchesSearch && matchesTipo) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }

            searchInput.addEventListener('input', filterTable);
            filterTipo.addEventListener('change', filterTable);
        });
    </script>
</body>
</html>
