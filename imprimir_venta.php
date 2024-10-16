<?php
// imprimir_venta.php

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Venta ID <?php echo htmlspecialchars($venta_id); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            background-color: #f3f4f6;
        }
        .receipt-container {
            width: 80mm;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
        }
        .receipt {
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #2563eb;
            color: white;
        }
        h1 {
            font-size: 16px;
            margin: 0;
        }
        h2 {
            font-size: 14px;
            margin: 15px 0 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        p {
            margin: 0 0 8px;
        }
        .info-group {
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            padding: 8px 4px;
        }
        th {
            background-color: #f3f4f6;
        }
        .total {
            font-weight: bold;
        }
        .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        .btn {
            padding: 8px 15px;
            text-decoration: none;
            color: white;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            border: none;
        }
        .btn-back {
            background-color: #6b7280;
        }
        .btn-print {
            background-color: #2563eb;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        @media print {
            body {
                background-color: white;
            }
            .receipt-container {
                width: 80mm;
                margin: 0 auto;
                box-shadow: none;
            }
            .no-print {
                display: none !important;
            }
            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt fade-in">
            <div class="header">
                <h1>Comprobante de Venta</h1>
                <p>ID: <?php echo htmlspecialchars($venta_id); ?></p>
            </div>
            
            <div class="info-group">
                <h2>Información de la Venta</h2>
                <p><strong>Tipo de Documento:</strong> <?php echo htmlspecialchars($tipo_documento); ?></p>
                <?php if ($tipo_documento === 'Factura'): ?>
                    <p><strong>Denominación:</strong> <?php echo htmlspecialchars($denominacion); ?></p>
                <?php endif; ?>
                <p><strong>Moneda:</strong> <?php echo htmlspecialchars($moneda); ?></p>
                <p><strong>Tipo de Cambio:</strong> <?php echo htmlspecialchars($tipo_cambio); ?></p>
                <p><strong>Fecha:</strong> <?php echo htmlspecialchars($fecha); ?></p>
            </div>

            <div class="info-group">
                <h2>Detalles Financieros</h2>
                <p><strong>Tasa de IGV:</strong> <?php echo htmlspecialchars(number_format($tasa_igv, 2)); ?>%</p>
                <p><strong>Subtotal sin IGV:</strong> <?php echo htmlspecialchars(number_format($subtotal_sin_igv_db, 2)); ?> <?php echo htmlspecialchars($moneda); ?></p>
                <p><strong>Total IGV:</strong> <?php echo htmlspecialchars(number_format($igv, 2)); ?> <?php echo htmlspecialchars($moneda); ?></p>
                <p><strong>Total:</strong> <?php echo htmlspecialchars(number_format($total_db, 2)); ?> <?php echo htmlspecialchars($moneda); ?></p>
            </div>

            <div class="info-group">
                <h2>Ítems de la Venta</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Producto/Servicio</th>
                            <th>Cant.</th>
                            <th>P.U.</th>
                            <th>Total</th>
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

            <div class="info-group">
                <h2>Observaciones</h2>
                <p><?php echo htmlspecialchars($observaciones_db); ?></p>
            </div>

            <div class="info-group">
                <h2>Estado</h2>
                <p><?php echo htmlspecialchars($estado); ?></p>
            </div>

            <div class="buttons no-print">
                <a href="ventas.php" class="btn btn-back">Volver</a>
                <button onclick="window.print()" class="btn btn-print">Imprimir</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            document.querySelectorAll('tr').forEach((row, index) => {
                row.style.animation = `fadeIn 0.3s ease-out ${index * 0.1}s both`;
            });
        });
    </script>
</body>
</html>