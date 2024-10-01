<?php
// detalle_venta.php

// Configuración de errores
ini_set('display_errors', 0); // Ocultar errores en pantalla
ini_set('log_errors', 1);     // Habilitar registro de errores
ini_set('error_log', __DIR__ . '/error_log.txt'); // Archivo de log
error_reporting(E_ALL);

require 'db.php';

// Obtener y sanitizar el venta_id desde la URL
$venta_id = isset($_GET['venta_id']) ? intval($_GET['venta_id']) : 0;

if ($venta_id > 0) {
    // Obtener detalles de la venta
    $stmt_venta = $conn->prepare("SELECT tipo_documento, denominacion, moneda, tipo_cambio, fecha, igv, subtotal, total, observaciones, estado FROM ventas WHERE id = ?");
    if ($stmt_venta) {
        $stmt_venta->bind_param("i", $venta_id);
        if ($stmt_venta->execute()) {
            $stmt_venta->bind_result($tipo_documento, $denominacion, $moneda, $tipo_cambio, $fecha, $igv, $subtotal_sin_igv_db, $total_db, $observaciones_db, $estado);
            if (!$stmt_venta->fetch()) {
                error_log("No se encontraron detalles para la venta ID: " . $venta_id);
                die("No se encontraron detalles para la venta especificada.");
            }
            $stmt_venta->close();
        } else {
            error_log("Error al ejecutar la consulta de venta: " . $stmt_venta->error);
            die("Error interno al obtener los detalles de la venta.");
        }
    } else {
        error_log("Error al preparar la consulta de venta: " . $conn->error);
        die("Error interno al preparar la consulta de venta.");
    }

    // Obtener ítems de la venta
    $stmt_items = $conn->prepare("SELECT p.nombre, vi.cantidad, vi.precio_unitario FROM venta_items vi JOIN productos p ON vi.producto_id = p.id WHERE vi.venta_id = ?");
    if ($stmt_items) {
        $stmt_items->bind_param("i", $venta_id);
        if ($stmt_items->execute()) {
            $result_items = $stmt_items->get_result();
            $stmt_items->close();
        } else {
            error_log("Error al ejecutar la consulta de ítems: " . $stmt_items->error);
            die("Error interno al obtener los ítems de la venta.");
        }
    } else {
        error_log("Error al preparar la consulta de ítems: " . $conn->error);
        die("Error interno al preparar la consulta de ítems.");
    }

    // Calcular la Tasa de IGV
    if ($subtotal_sin_igv_db > 0) {
        $tasa_igv = ($igv / $subtotal_sin_igv_db) * 100;
    } else {
        $tasa_igv = 0;
    }
} else {
    die("Venta inválida.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Venta</title>
    <!-- Bootstrap CSS -->
    <link href="./css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estilos generales */
        body {
            margin: 20px;
            font-size: 12px; /* Reducir tamaño de fuente para ajustarse mejor a la etiqueta */
        }

        h1, h3 {
            font-size: 18px;
        }

        /* Estilos específicos para impresión */
        @media print {
            .no-print {
                display: none;
            }
            body {
                font-size: 10px; /* Ajustar tamaño de fuente para etiqueta más pequeña */
                margin: 0; /* Eliminar márgenes para aprovechar todo el espacio */
            }
            .container {
                width: 100%; /* Asegurar que el contenedor use todo el ancho disponible */
            }
            table {
                width: 100%;
                font-size: 10px; /* Reducir tamaño de fuente de la tabla */
            }
            th, td {
                padding: 4px; /* Reducir padding para ahorrar espacio */
                text-align: left;
                vertical-align: top;
            }
            /* Definir tamaño de página para etiquetera (ajustar según necesidad) */
            @page {
                size: 80mm auto; /* Ancho fijo, altura automática */
                margin: 0;
            }
        }

        /* Estilos para pantalla */
        @media screen {
            .btn-print {
                margin-bottom: 20px;
            }
        }

        /* Tabla estilizada */
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }

        table, th, td {
            border: 1px solid #dee2e6;
        }

        th, td {
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Detalle de Venta ID <?php echo htmlspecialchars($venta_id); ?></h1>

        <div class="mb-3 no-print">
            <a href="ventas.php" class="btn btn-secondary">Volver</a>
            <button class="btn btn-primary" onclick="window.print()">Imprimir</button>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                Información de la Venta
            </div>
            <div class="card-body">
                <p><strong>Tipo de Documento:</strong> <?php echo htmlspecialchars($tipo_documento); ?></p>
                <?php if ($tipo_documento === 'Factura'): ?>
                    <p><strong>Denominación:</strong> <?php echo htmlspecialchars($denominacion); ?></p>
                <?php endif; ?>
                <p><strong>Moneda:</strong> <?php echo htmlspecialchars($moneda); ?></p>
                <p><strong>Tipo de Cambio:</strong> <?php echo htmlspecialchars($tipo_cambio); ?></p>
                <p><strong>Fecha:</strong> <?php echo htmlspecialchars($fecha); ?></p>
                
                <!-- Mostrar la Tasa de IGV calculada -->
                <p><strong>Tasa de IGV:</strong> <?php echo htmlspecialchars(number_format($tasa_igv, 2)); ?>%</p>
                
                <p><strong>Subtotal sin IGV:</strong> <?php echo htmlspecialchars(number_format($subtotal_sin_igv_db, 2)); ?> <?php echo htmlspecialchars($moneda); ?></p>
                
                <!-- Mostrar el monto del IGV directamente -->
                <p><strong>Total IGV:</strong> <?php echo htmlspecialchars(number_format($igv, 2)); ?> <?php echo htmlspecialchars($moneda); ?></p>
                
                <p><strong>Total:</strong> <?php echo htmlspecialchars(number_format($total_db, 2)); ?> <?php echo htmlspecialchars($moneda); ?></p>
                <p><strong>Observaciones:</strong> <?php echo htmlspecialchars($observaciones_db); ?></p>
                <p><strong>Estado:</strong> <?php echo htmlspecialchars($estado); ?></p>
            </div>
        </div>

        <h3>Ítems de la Venta</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 40%;">Producto/Servicio</th>
                    <th style="width: 15%;">Cantidad</th>
                    <th style="width: 20%;">Precio Unitario</th>
                    <th style="width: 25%;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_items->num_rows > 0): ?>
                    <?php while ($item = $result_items->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($item['cantidad']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($item['precio_unitario'], 2)); ?></td>
                            <td><?php echo htmlspecialchars(number_format($item['cantidad'] * $item['precio_unitario'], 2)); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No hay ítems para mostrar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS (Opcional) -->
    <script src="./js/bootstrap.bundle.min.js"></script>
</body>
</html>
