-- Tabla para registrar los cambios en usuarios
CREATE TABLE audit_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id VARCHAR(20) NOT NULL, -- ID del usuario auditado
  accion VARCHAR(10) NOT NULL, -- 'INSERT', 'UPDATE' o 'DELETE'
  usuario_modificador VARCHAR(20), -- ID del usuario que hizo el cambio
  sql_usuario VARCHAR(255), -- Usuario de la sesión SQL que realizó el cambio
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  detalles TEXT -- Detalles del cambio
);

-- Trigger para inserciones en usuarios
DELIMITER //
CREATE TRIGGER auditoria_usuario_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
  INSERT INTO audit_users (usuario_id, accion, usuario_modificador, sql_usuario, detalles)
  VALUES (
    NEW.id, 
    'INSERT', 
    NEW.id, -- En una inserción, el usuario que se crea es el mismo que "se modifica"
    CURRENT_USER(),       
    CONCAT('Se creó el usuario: ', NEW.name, ' (', NEW.email, '). Rol: ', NEW.role)
  );
END//
DELIMITER ;

-- Trigger para actualizaciones en usuarios
DELIMITER //
CREATE TRIGGER auditoria_usuario_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
  DECLARE cambios TEXT DEFAULT '';
  
  -- Verificar cambios en los campos principales
  IF (NEW.name != OLD.name) THEN
    SET cambios = CONCAT(cambios, 'Nombre: ', OLD.name, ' → ', NEW.name, '. ');
  END IF;
  
  IF (NEW.email != OLD.email) THEN
    SET cambios = CONCAT(cambios, 'Email: ', OLD.email, ' → ', NEW.email, '. ');
  END IF;
  
  IF (NEW.role != OLD.role) THEN
    SET cambios = CONCAT(cambios, 'Rol: ', OLD.role, ' → ', NEW.role, '. ');
  END IF;
  
  IF (NEW.is_blocked != OLD.is_blocked) THEN
    SET cambios = CONCAT(cambios, 'Bloqueo: ', OLD.is_blocked, ' → ', NEW.is_blocked, '. ');
  END IF;
  
  IF (NEW.phone != OLD.phone OR (NEW.phone IS NULL AND OLD.phone IS NOT NULL) OR (NEW.phone IS NOT NULL AND OLD.phone IS NULL)) THEN
    SET cambios = CONCAT(cambios, 'Teléfono: ', COALESCE(OLD.phone, 'NULL'), ' → ', COALESCE(NEW.phone, 'NULL'), '. ');
  END IF;
  
  -- Solo registrar si hubo cambios relevantes
  IF (LENGTH(cambios) > 0) THEN
    INSERT INTO audit_users (usuario_id, accion, usuario_modificador, sql_usuario, detalles)
    VALUES (
      NEW.id, 
      'UPDATE', 
      NEW.id, -- Asumiendo que el usuario se modifica a sí mismo o hay otro sistema de tracking
      CURRENT_USER(),    
      CONCAT('Usuario actualizado. Cambios: ', cambios)
    );
  END IF;
END//
DELIMITER ;

-- Trigger para eliminaciones en usuarios
DELIMITER //
CREATE TRIGGER auditoria_usuario_delete
BEFORE DELETE ON users
FOR EACH ROW
BEGIN
  INSERT INTO audit_users (usuario_id, accion, usuario_modificador, sql_usuario, detalles)
  VALUES (
    OLD.id, 
    'DELETE', 
    OLD.id, -- El usuario que se elimina
    CURRENT_USER(),        
    CONCAT('Se eliminó el usuario: ', OLD.name, ' (', OLD.email, '). Rol: ', OLD.role)
  );
END//
DELIMITER ;



-- Prueba de inserción (debería funcionar con usuarios existentes en tu base de datos)
INSERT INTO users (
  id,
  name,
  email,
  role
)
VALUES (
  'TEST_USER_001',
  'Usuario Prueba',
  'prueba@ejemplo.com',
  'customer'
);

-- Prueba de actualización
UPDATE users 
SET name = 'Usuario Modificado', role = 'admin' 
WHERE id = 'TEST_USER_001';

-- Prueba de eliminación
DELETE FROM users WHERE id = 'TEST_USER_001';