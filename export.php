<?php
// export.php

ini_set('display_errors', 0); // Ocultar errores en producción
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');
error_reporting(E_ALL);

require 'db.php';

// Definir los tipos de exportación permitidos
$allowed_types = ['inventario', 'catalogo', 'anulados', 'ventas', 'historial'];

// Verificar el tipo de exportación
if (!isset($_GET['type']) || !in_array($_GET['type'], $allowed_types)) {
    die("Tipo de exportación no especificado o no válido.");
}

$type = $_GET['type'];

// Definir el nombre del archivo CSV y la consulta SQL
$filename = "";
$columns = [];
$sql = "";
$params = [];
$types = "";

// Función para agregar filtros a la consulta
function apply_filters(&$query, &$params, &$types, $search, $filter_usuario, $filter_fecha, $filter_tipo, $type_specific = false) {
    if ($search !== '') {
        if ($type_specific) {
            $query .= " AND p.nombre LIKE ? ";
        } else {
            $query .= " AND nombre LIKE ? ";
        }
        $params[] = '%' . $search . '%';
        $types .= "s";
    }

    if ($filter_usuario !== '') {
        $query .= " AND h.usuario = ? ";
        $params[] = $filter_usuario;
        $types .= "s";
    }

    if ($filter_fecha !== '') {
        $query .= " AND DATE(h.fecha) = ? ";
        $params[] = $filter_fecha;
        $types .= "s";
    }

    if ($filter_tipo !== '') {
        $query .= " AND tipo = ? ";
        $params[] = $filter_tipo;
        $types .= "s";
    }
}

switch ($type) {
    case 'inventario':
        $filename = "inventario_" . date('Ymd') . ".csv";
        $sql = "SELECT id, tipo, nombre, codigo, precio, stock FROM productos";
        $columns = ['ID', 'Tipo', 'Nombre', 'Código', 'Precio Unitario', 'Stock'];
        break;

    case 'catalogo':
        $filename = "catalogo_" . date('Ymd') . ".csv";
        // Incluir tanto productos como servicios
        $sql = "SELECT id, tipo, nombre, codigo, precio FROM productos";
        $columns = ['ID', 'Tipo', 'Nombre', 'Código', 'Precio Unitario'];
        break;

    case 'anulados':
        $filename = "anulados_" . date('Ymd') . ".csv";
        $sql = "SELECT v.id, v.fecha, v.tipo_documento, v.denominacion, v.moneda, v.total, a.fecha_anulacion
                FROM ventas v
                JOIN anulados a ON v.id = a.venta_id
                ORDER BY a.fecha_anulacion DESC";
        $columns = ['ID de Venta', 'Fecha de Venta', 'Tipo de Documento', 'Denominación', 'Moneda', 'Total', 'Fecha de Anulación'];
        break;

    case 'ventas':
        $filename = "ventas_completo_" . date('Ymd') . ".csv";
        $sql = "SELECT 
                    v.id AS venta_id,
                    v.tipo_documento,
                    v.moneda,
                    v.tipo_cambio,
                    v.fecha,
                    v.igv AS tasa_igv,
                    v.subtotal AS subtotal_sin_igv,
                    v.igv AS total_igv,
                    v.total,
                    v.observaciones,
                    v.estado,
                    p.nombre AS producto,
                    vi.cantidad,
                    vi.precio_unitario,
                    (vi.cantidad * vi.precio_unitario) AS total_item,
                    p.codigo AS codigo_producto
                FROM ventas v
                JOIN venta_items vi ON v.id = vi.venta_id
                JOIN productos p ON vi.producto_id = p.id
                ORDER BY v.fecha DESC, v.id ASC";
        $columns = [
            'ID de Venta',
            'Tipo de Documento',
            'Moneda',
            'Tipo de Cambio',
            'Fecha',
            'Tasa de IGV',
            'Subtotal sin IGV',
            'Total IGV',
            'Total',
            'Observaciones',
            'Estado',
            'Producto',
            'Cantidad',
            'Precio Unitario',
            'Total Item',
            'Código de Producto'
        ];
        break;

    case 'historial':
        $filename = "historial_" . date('Ymd') . ".csv";
        // Obtener filtros si existen
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $filter_usuario = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';
        $filter_fecha = isset($_GET['fecha']) ? trim($_GET['fecha']) : '';
        $filter_tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';

        // Construir la consulta con filtros para ambos historiales
        $sql = "
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

        apply_filters($sql, $params, $types, $search, $filter_usuario, $filter_fecha, $filter_tipo, true);

        $sql .= " 

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

        apply_filters($sql, $params, $types, $search, $filter_usuario, $filter_fecha, $filter_tipo, false);

        $sql .= " ORDER BY fecha DESC";

        $columns = ['ID Historial', 'Tipo', 'ID Producto/Venta', 'Nombre Producto/Tipo Documento', 'Acción', 'Usuario', 'Fecha'];
        break;

    default:
        die("Tipo de exportación no soportado.");
}

// Ejecutar la consulta con preparación de sentencias si es necesario
if ($type === 'historial') {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Error en la preparación de la consulta: " . $conn->error);
        die("Error interno al preparar la exportación.");
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("Error al ejecutar la consulta: " . $stmt->error);
        die("Error interno al ejecutar la exportación.");
    }

    $result = $stmt->get_result();
    if ($result === false) {
        error_log("Error al obtener los resultados: " . $stmt->error);
        die("Error interno al obtener los datos para la exportación.");
    }
} else {
    // Para otros tipos que no requieren filtros
    $result = $conn->query($sql);
    if ($result === false) {
        die("Error en la consulta: " . $conn->error);
    }
}

// Establecer los encabezados para la descarga del archivo CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Crear un archivo temporal de salida
$output = fopen('php://output', 'w');

// Escribir los encabezados en el CSV
fputcsv($output, $columns);

// Función para mapear y obtener valores de las filas
function map_row($type, $row, $columns) {
    $csv_row = [];
    foreach ($columns as $column) {
        switch ($type) {
            case 'inventario':
                $field_map = [
                    'ID' => 'id',
                    'Tipo' => 'tipo',
                    'Nombre' => 'nombre',
                    'Código' => 'codigo',
                    'Precio Unitario' => 'precio',
                    'Stock' => 'stock'
                ];
                break;

            case 'catalogo':
                $field_map = [
                    'ID' => 'id',
                    'Tipo' => 'tipo',
                    'Nombre' => 'nombre',
                    'Código' => 'codigo',
                    'Precio Unitario' => 'precio'
                ];
                break;

            case 'anulados':
                $field_map = [
                    'ID de Venta' => 'id',
                    'Fecha de Venta' => 'fecha',
                    'Tipo de Documento' => 'tipo_documento',
                    'Denominación' => 'denominacion',
                    'Moneda' => 'moneda',
                    'Total' => 'total',
                    'Fecha de Anulación' => 'fecha_anulacion'
                ];
                break;

            case 'ventas':
                $field_map = [
                    'ID de Venta' => 'venta_id',
                    'Tipo de Documento' => 'tipo_documento',
                    'Moneda' => 'moneda',
                    'Tipo de Cambio' => 'tipo_cambio',
                    'Fecha' => 'fecha',
                    'Tasa de IGV' => 'tasa_igv',
                    'Subtotal sin IGV' => 'subtotal_sin_igv',
                    'Total IGV' => 'total_igv',
                    'Total' => 'total',
                    'Observaciones' => 'observaciones',
                    'Estado' => 'estado',
                    'Producto' => 'producto',
                    'Cantidad' => 'cantidad',
                    'Precio Unitario' => 'precio_unitario',
                    'Total Item' => 'total_item',
                    'Código de Producto' => 'codigo_producto'
                ];
                break;

            case 'historial':
                $field_map = [
                    'ID Historial' => 'historial_id',
                    'Tipo' => 'tipo',
                    'ID Producto/Venta' => 'producto_id',
                    'Nombre Producto/Tipo Documento' => 'producto_nombre',
                    'Acción' => 'accion',
                    'Usuario' => 'usuario',
                    'Fecha' => 'fecha'
                ];
                break;

            default:
                $field_map = [];
        }

        $field = isset($field_map[$column]) ? $field_map[$column] : '';
        if ($field) {
            if (in_array($field, ['total', 'precio', 'precio_unitario', 'subtotal_sin_igv', 'total_igv', 'total_item'])) {
                $value = isset($row[$field]) ? number_format($row[$field], 2) : '0.00';
            } elseif ($field === 'fecha') {
                $value = isset($row[$field]) ? date('Y-m-d H:i:s', strtotime($row[$field])) : 'N/A';
            } else {
                $value = isset($row[$field]) ? $row[$field] : 'N/A';
            }
        } else {
            $value = 'N/A';
        }

        $csv_row[] = $value;
    }
    return $csv_row;
}

// Escribir los datos en el CSV
while ($row = $result->fetch_assoc()) {
    $csv_row = map_row($type, $row, $columns);
    fputcsv($output, $csv_row);
}

fclose($output);
exit();
?>
