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
<html lang="es" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestión de Ventas y Almacén</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .slide-in {
            animation: slideIn 0.5s ease-in-out;
        }
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body class="h-full">
    <div class="min-h-full">
        <nav class="bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <img class="h-8 w-8" src="./favicon.ico" alt="Your Company">
                        </div>
                        <div class="hidden md:block">
                            <div class="ml-10 flex items-baseline space-x-4">
                                <a href="index.php" class="bg-gray-900 text-white rounded-md px-3 py-2 text-sm font-medium" aria-current="page">Dashboard</a>
                                <a href="ventas.php" class="text-gray-300 hover:bg-gray-700 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Ventas</a>
                                <a href="almacen.php" class="text-gray-300 hover:bg-gray-700 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Almacén</a>
                                <a href="catalogo.php" class="text-gray-300 hover:bg-gray-700 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Catálogo</a>
                                <a href="historial.php" class="text-gray-300 hover:bg-gray-700 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Historial</a>
                            </div>
                        </div>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-4 flex items-center md:ml-6">
                            <button type="button" class="rounded-full bg-gray-800 p-1 text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800">
                                <span class="sr-only">View notifications</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <header class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">Dashboard</h1>
            </div>
        </header>

        <main>
            <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
                <!-- Filtros -->
                <div class="bg-white px-4 py-5 shadow sm:rounded-lg sm:p-6 mb-6 fade-in">
                    <div class="md:grid md:grid-cols-4 md:gap-6">
                        <div class="md:col-span-1">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Filtros</h3>
                            <p class="mt-1 text-sm text-gray-500">Seleccione el rango de fechas y tipo de documento para filtrar los datos.</p>
                        </div>
                        <div class="mt-5 md:col-span-3 md:mt-0">
                            <form action="#" method="GET">
                                <div class="grid grid-cols-6 gap-6">
                                    <div class="col-span-6 sm:col-span-2">
                                        <label for="fecha_inicio" class="block text-sm font-medium text-gray-700">Fecha Inicio</label>
                                        <input type="date" name="fecha_inicio" id="fecha_inicio" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                                    </div>
                                    <div class="col-span-6 sm:col-span-2">
                                        <label for="fecha_fin" class="block text-sm font-medium text-gray-700">Fecha Fin</label>
                                        <input type="date" name="fecha_fin" id="fecha_fin" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                                    </div>
                                    <div class="col-span-6 sm:col-span-2">
                                        <label for="tipo_documento" class="block text-sm font-medium text-gray-700">Tipo de Documento</label>
                                        <select id="tipo_documento" name="tipo_documento" class="mt-1 block w-full rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                                            <option value="Todos" <?php echo ($tipo_documento_filtro == 'Todos') ? 'selected' : ''; ?>>Todos</option>
                                            <option value="Boleta" <?php echo ($tipo_documento_filtro == 'Boleta') ? 'selected' : ''; ?>>Boleta</option>
                                            <option value="Factura" <?php echo ($tipo_documento_filtro == 'Factura') ? 'selected' : ''; ?>>Factura</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-5 text-right">
                                    <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Aplicar Filtros</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Indicadores -->
                <div class="mt-8">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="bg-white overflow-hidden shadow rounded-lg slide-in">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Ventas Totales</dt>
                                            <dd class="text-3xl font-semibold text-gray-900"><?php echo htmlspecialchars($totalVentas); ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white overflow-hidden shadow rounded-lg slide-in">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Ventas Activas</dt>
                                            <dd class="text-3xl font-semibold text-gray-900"><?php echo htmlspecialchars($ventasActivas); ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white overflow-hidden shadow rounded-lg slide-in">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Ventas Anuladas</dt>
                                            <dd class="text-3xl font-semibold text-gray-900"><?php echo htmlspecialchars($ventasAnuladas); ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white overflow-hidden shadow rounded-lg slide-in">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Ingresos Totales</dt>
                                            <dd class="text-3xl font-semibold text-gray-900"><?php echo htmlspecialchars(number_format($ingresosTotales, 2)); ?> PEN</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="mt-8">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="bg-white overflow-hidden shadow rounded-lg fade-in">
                            <div class="p-5">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Ventas por Mes</h3>
                                <div class="mt-5">
                                    <canvas id="ventasMesChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white overflow-hidden shadow rounded-lg fade-in">
                            <div class="p-5">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Productos Más Vendidos</h3>
                                <div class="mt-5">
                                    <canvas id="productosVendidosChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Productos Más Vendidos -->
                <div class="mt-8">
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg fade-in">
                        <div class="px-4 py-5 sm:px-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Top Productos/Servicios</h3>
                        </div>
                        <div class="border-t border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto/Servicio</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Vendido</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (!empty($productosMasVendidos)): ?>
                                        <?php foreach ($productosMasVendidos as $producto): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($producto['total_vendido']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No hay datos para mostrar.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Datos para Ventas por Mes
        const ventasPorMes = <?php echo json_encode(array_values($ventasPorMes)); ?>;
        const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                       'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        const ctxVentasMes = document.getElementById('ventasMesChart').getContext('2d');
        new Chart(ctxVentasMes, {
            type: 'bar',
            data: {
                labels: meses,
                datasets: [{
                    label: 'Ventas por Mes',
                    data: ventasPorMes,
                    backgroundColor: 'rgba(79, 70, 229, 0.6)',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        precision: 0
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
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
        new Chart(ctxProductosVendidos, {
            type: 'doughnut',
            data: {
                labels: productosVendidos,
                datasets: [{
                    label: 'Productos Más Vendidos',
                    data: totalVendidos,
                    backgroundColor: [
                        'rgba(79, 70, 229, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(59, 130, 246, 0.8)'
                    ],
                    borderColor: [
                        'rgba(79, 70, 229, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(239, 68, 68, 1)',
                        'rgba(59, 130, 246, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
    </script>
</body>
</html>