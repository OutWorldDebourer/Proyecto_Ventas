-- Script de inicialización de la base de datos

-- 1. Crear la base de datos si no existe y seleccionarla con codificación UTF8MB4
CREATE DATABASE IF NOT EXISTS ventas_db DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
USE ventas_db;

-- 2. Crear la tabla 'productos'
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('Producto', 'Servicio') NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    codigo VARCHAR(100) NOT NULL UNIQUE,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    INDEX idx_tipo (tipo),
    INDEX idx_nombre (nombre),
    INDEX idx_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 3. Crear la tabla 'ventas' con el campo 'estado' corregido a minúsculas
CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    denominacion VARCHAR(255),
    tipo_documento ENUM('Factura', 'Boleta') NOT NULL,
    moneda VARCHAR(50) NOT NULL,
    tipo_cambio DECIMAL(10,4) NOT NULL DEFAULT 1.0000,
    fecha DATE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    igv DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    observaciones TEXT,
    -- Se eliminó el campo 'anulado' ya que 'estado' maneja esta información
    estado ENUM('activo', 'anulado') NOT NULL DEFAULT 'activo',
    INDEX idx_tipo_documento (tipo_documento),
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 4. Crear la tabla 'venta_items'
CREATE TABLE IF NOT EXISTS venta_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 0,
    precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_venta_id (venta_id),
    INDEX idx_producto_id (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 5. Crear la tabla 'historial'
CREATE TABLE IF NOT EXISTS historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    accion VARCHAR(255) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario VARCHAR(100) NOT NULL,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    INDEX idx_venta_id (venta_id),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 6. Crear la tabla 'historial_inventario'
CREATE TABLE IF NOT EXISTS historial_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    accion VARCHAR(255) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario VARCHAR(100) NOT NULL,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_producto_id (producto_id),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 7. Crear la tabla 'compras'
CREATE TABLE IF NOT EXISTS compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    tipo_doc ENUM('DNI', 'RUC', 'OTROS') NOT NULL DEFAULT 'DNI',
    numero_doc VARCHAR(20) DEFAULT NULL,
    entidad VARCHAR(255) DEFAULT NULL,
    direccion_fiscal VARCHAR(255) DEFAULT NULL,
    telefonos VARCHAR(100) DEFAULT NULL, -- Almacena múltiples teléfonos separados por comas
    emails VARCHAR(255) DEFAULT NULL,     -- Almacena múltiples emails separados por comas
    otros TEXT DEFAULT NULL,
    monto DECIMAL(10,2) NOT NULL,
    moneda VARCHAR(10) NOT NULL DEFAULT 'PEN',
    tipo_cambio DECIMAL(10,4) NOT NULL DEFAULT 1.0000,
    fecha_compra DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_producto_id (producto_id),
    INDEX idx_fecha_compra (fecha_compra)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
