-- Tabla para registrar los cambios
CREATE TABLE audit_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  orden_id INT NOT NULL,
  accion VARCHAR(10) NOT NULL, -- 'INSERT', 'UPDATE' o 'DELETE'
  usuario_id VARCHAR(20), -- ID del usuario que hizo el cambio
  sql_usuario VARCHAR(255), -- Usuario de la sesión SQL que realizó el cambio',
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  detalles TEXT -- Detalles del cambio
);



-- Trigger para inserciones modificado
DELIMITER //
CREATE TRIGGER auditoria_orden_insert
AFTER INSERT ON orders
FOR EACH ROW
BEGIN
  INSERT INTO audit_orders (orden_id, accion, usuario_id, sql_usuario, detalles)
  VALUES (
    NEW.id, 
    'INSERT', 
    NEW.user_id,          
    CURRENT_USER(),       
    CONCAT('Se creó la orden #', NEW.order_number, ' con total $', NEW.total)
  );
END//
DELIMITER ;

-- Trigger para actualizaciones modificado
DELIMITER //
CREATE TRIGGER auditoria_orden_update
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
  IF (NEW.status != OLD.status OR NEW.total != OLD.total) THEN
    INSERT INTO audit_orders (orden_id, accion, usuario_id, sql_usuario, detalles)
    VALUES (
      NEW.id, 
      'UPDATE', 
      NEW.user_id,         
      CURRENT_USER(),    
      CONCAT('Orden actualizada. Estado: ', OLD.status, ' → ', NEW.status, 
             '. Total: $', OLD.total, ' → $', NEW.total)
    );
  END IF;
END//
DELIMITER ;

-- Trigger para eliminaciones modificado
DELIMITER //
CREATE TRIGGER auditoria_orden_delete
BEFORE DELETE ON orders
FOR EACH ROW
BEGIN
  INSERT INTO audit_orders (orden_id, accion, usuario_id, sql_usuario, detalles)
  VALUES (
    OLD.id, 
    'DELETE', 
    OLD.user_id,         
    CURRENT_USER(),        
    CONCAT('Se eliminó la orden #', OLD.order_number, ' con total $', OLD.total)
  );
END//
DELIMITER ;


-- sentencia de prueba:
INSERT INTO orders (
  order_number,
  user_id,
  status,
  subtotal,
  total
)
VALUES (
  'TEST001',      -- Número de orden de prueba
  '6861e06ddcf49',-- ID de usuario 
  'pending',      -- Estado de la orden
  0.00,           -- Subtotal
  0.00            -- Total
);