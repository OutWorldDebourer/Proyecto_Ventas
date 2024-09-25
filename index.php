<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
require 'db.php';

// Obtener filtros de la solicitud GET
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-01-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
$tipo_documento_filtro = isset($_GET['tipo_documento']) ? $_GET['tipo_documento'] : 'Todos';

// Funciones para obtener datos del dashboard con filtros
function obtenerTotalVentas($conn, $fecha_inicio, $fecha_fin, $tipo_documento) {
    if ($tipo_documento == 'Todos') {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ventas WHERE fecha BETWEEN ? AND ?");
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ventas WHERE fecha BETWEEN ? AND ? AND tipo_documento = ?");
        $stmt->bind_param("sss", $fecha_inicio, $fecha_fin, $tipo_documento);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];
    $stmt->close();
    return $total;
}

function obtenerVentasActivas($conn, $fecha_inicio, $fecha_fin, $tipo_documento) {
    if ($tipo_documento == 'Todos') {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ventas WHERE fecha BETWEEN ? AND ? AND estado = 'activo'");
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ventas WHERE fecha BETWEEN ? AND ? AND tipo_documento = ? AND estado = 'activo'");
        $stmt->bind_param("sss", $fecha_inicio, $fecha_fin, $tipo_documento);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];
    $stmt->close();
    return $total;
}

function obtenerVentasAnuladas($conn, $fecha_inicio, $fecha_fin, $tipo_documento) {
    if ($tipo_documento == 'Todos') {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ventas WHERE fecha BETWEEN ? AND ? AND estado = 'anulado'");
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ventas WHERE fecha BETWEEN ? AND ? AND tipo_documento = ? AND estado = 'anulado'");
        $stmt->bind_param("sss", $fecha_inicio, $fecha_fin, $tipo_documento);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];
    $stmt->close();
    return $total;
}

function obtenerIngresosTotales($conn, $fecha_inicio, $fecha_fin, $tipo_documento) {
    if ($tipo_documento == 'Todos') {
        $stmt = $conn->prepare("SELECT SUM(total) as ingresos FROM ventas WHERE fecha BETWEEN ? AND ? AND estado = 'activo'");
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    } else {
        $stmt = $conn->prepare("SELECT SUM(total) as ingresos FROM ventas WHERE fecha BETWEEN ? AND ? AND tipo_documento = ? AND estado = 'activo'");
        $stmt->bind_param("sss", $fecha_inicio, $fecha_fin, $tipo_documento);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $ingresos = $result->fetch_assoc()['ingresos'];
    $stmt->close();
    return $ingresos ?? 0;
}

function obtenerVentasPorMes($conn, $fecha_inicio, $fecha_fin, $tipo_documento) {
    if ($tipo_documento == 'Todos') {
        $stmt = $conn->prepare("
            SELECT 
                MONTH(fecha) as mes, 
                COUNT(*) as total 
            FROM ventas 
            WHERE fecha BETWEEN ? AND ? 
            GROUP BY mes
            ORDER BY mes
        ");
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    } else {
        $stmt = $conn->prepare("
            SELECT 
                MONTH(fecha) as mes, 
                COUNT(*) as total 
            FROM ventas 
            WHERE fecha BETWEEN ? AND ? AND tipo_documento = ?
            GROUP BY mes
            ORDER BY mes
        ");
        $stmt->bind_param("sss", $fecha_inicio, $fecha_fin, $tipo_documento);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $ventas = [];
    while ($row = $result->fetch_assoc()) {
        $ventas[$row['mes']] = $row['total'];
    }
    $stmt->close();

    // Asegurar que todos los meses estén representados
    $ventasPorMes = [];
    for ($i = 1; $i <= 12; $i++) {
        $ventasPorMes[$i] = $ventas[$i] ?? 0;
    }

    return $ventasPorMes;
}

function obtenerProductosMasVendidos($conn, $fecha_inicio, $fecha_fin, $tipo_documento, $limite = 5) {
    if ($tipo_documento == 'Todos') {
        $stmt = $conn->prepare("
            SELECT 
                p.nombre, 
                SUM(vi.cantidad) as total_vendido 
            FROM venta_items vi 
            JOIN productos p ON vi.producto_id = p.id 
            JOIN ventas v ON vi.venta_id = v.id
            WHERE v.fecha BETWEEN ? AND ? AND v.estado = 'activo'
            GROUP BY vi.producto_id 
            ORDER BY total_vendido DESC 
            LIMIT ?
        ");
        $stmt->bind_param("ssi", $fecha_inicio, $fecha_fin, $limite);
    } else {
        $stmt = $conn->prepare("
            SELECT 
                p.nombre, 
                SUM(vi.cantidad) as total_vendido 
            FROM venta_items vi 
            JOIN productos p ON vi.producto_id = p.id 
            JOIN ventas v ON vi.venta_id = v.id
            WHERE v.fecha BETWEEN ? AND ? AND v.tipo_documento = ? AND v.estado = 'activo'
            GROUP BY vi.producto_id 
            ORDER BY total_vendido DESC 
            LIMIT ?
        ");
        $stmt->bind_param("sssi", $fecha_inicio, $fecha_fin, $tipo_documento, $limite);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    $stmt->close();
    return $productos;
}

// Obtener datos para el dashboard con filtros
$totalVentas = obtenerTotalVentas($conn, $fecha_inicio, $fecha_fin, $tipo_documento_filtro);
$ventasActivas = obtenerVentasActivas($conn, $fecha_inicio, $fecha_fin, $tipo_documento_filtro);
$ventasAnuladas = obtenerVentasAnuladas($conn, $fecha_inicio, $fecha_fin, $tipo_documento_filtro);
$ingresosTotales = obtenerIngresosTotales($conn, $fecha_inicio, $fecha_fin, $tipo_documento_filtro);
$ventasPorMes = obtenerVentasPorMes($conn, $fecha_inicio, $fecha_fin, $tipo_documento_filtro);
$productosMasVendidos = obtenerProductosMasVendidos($conn, $fecha_inicio, $fecha_fin, $tipo_documento_filtro);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio - Gestión de Ventas y Almacén</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="./css/styles.css">
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

    <!-- Dashboard -->
    <div class="container mt-5">
        <h1 class="mb-4">Dashboard de Gestión de Ventas</h1>
        
        <!-- Filtros -->
        <form method="GET" class="mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label">Fecha Inicio:</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($fecha_inicio); ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label">Fecha Fin:</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($fecha_fin); ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="tipo_documento" class="form-label">Tipo de Documento:</label>
                    <select id="tipo_documento" name="tipo_documento" class="form-select">
                        <option value="Todos" <?php echo ($tipo_documento_filtro == 'Todos') ? 'selected' : ''; ?>>Todos</option>
                        <option value="Boleta" <?php echo ($tipo_documento_filtro == 'Boleta') ? 'selected' : ''; ?>>Boleta</option>
                        <option value="Factura" <?php echo ($tipo_documento_filtro == 'Factura') ? 'selected' : ''; ?>>Factura</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Aplicar Filtros</button>
                </div>
            </div>
        </form>

        <div class="row">
            <!-- Indicadores -->
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-header">Ventas Totales</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($totalVentas); ?></h5>
                        <p class="card-text">Número total de ventas realizadas.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-header">Ventas Activas</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($ventasActivas); ?></h5>
                        <p class="card-text">Ventas que están activas.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-header">Ventas Anuladas</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($ventasAnuladas); ?></h5>
                        <p class="card-text">Ventas que han sido anuladas.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-danger mb-3">
                    <div class="card-header">Ingresos Totales</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars(number_format($ingresosTotales, 2)); ?> PEN</h5>
                        <p class="card-text">Ingresos generados por las ventas.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row">
            <div class="col-md-6">
                <canvas id="ventasMesChart"></canvas>
            </div>
            <div class="col-md-6">
                <canvas id="productosVendidosChart"></canvas>
            </div>
        </div>

        <!-- Tabla de Productos Más Vendidos -->
        <div class="row mt-5">
            <div class="col-md-12">
                <h3>Productos Más Vendidos</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Producto/Servicio</th>
                            <th>Total Vendido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($productosMasVendidos)): ?>
                            <?php foreach ($productosMasVendidos as $producto): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($producto['total_vendido']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">No hay datos para mostrar.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts para Chart.js -->
    <script>
        // Datos para Ventas por Mes
        const ventasPorMes = <?php echo json_encode(array_values($ventasPorMes)); ?>;
        const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                       'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        const ctxVentasMes = document.getElementById('ventasMesChart').getContext('2d');
        const ventasMesChart = new Chart(ctxVentasMes, {
            type: 'bar',
            data: {
                labels: meses,
                datasets: [{
                    label: 'Ventas por Mes',
                    data: ventasPorMes,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        precision:0
                    }
                }
            }
        });

        // Datos para Productos Más Vendidos
        const productosVendidos = <?php 
            $productosNombres = array_map(function($producto) {
                return $producto['nombre'];
            }, $productosMasVendidos);
            echo json_encode($productosNombres);
        ?>;
        const totalVendidos = <?php 
            $productosTotal = array_map(function($producto) {
                return $producto['total_vendido'];
            }, $productosMasVendidos);
            echo json_encode($productosTotal);
        ?>;

        const ctxProductosVendidos = document.getElementById('productosVendidosChart').getContext('2d');
        const productosVendidosChart = new Chart(ctxProductosVendidos, {
            type: 'pie',
            data: {
                labels: productosVendidos,
                datasets: [{
                    label: 'Productos Más Vendidos',
                    data: totalVendidos,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 206, 86, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132,1)',
                        'rgba(54, 162, 235,1)',
                        'rgba(255, 206, 86,1)',
                        'rgba(75, 192, 192,1)',
                        'rgba(153, 102, 255,1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
