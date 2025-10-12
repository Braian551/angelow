<?php
require_once __DIR__ . '/../../../conexion.php';

echo "=== VERIFICANDO TRIGGERS ===\n\n";

$stmt = $conn->query("
    SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE 
    FROM information_schema.TRIGGERS 
    WHERE TRIGGER_SCHEMA = 'angelow' 
    AND EVENT_OBJECT_TABLE = 'orders'
");

$triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($triggers) > 0) {
    echo "✅ Triggers encontrados:\n\n";
    foreach ($triggers as $trigger) {
        echo "   • {$trigger['TRIGGER_NAME']}\n";
        echo "     Evento: {$trigger['EVENT_MANIPULATION']} on {$trigger['EVENT_OBJECT_TABLE']}\n\n";
    }
} else {
    echo "⚠️  No se encontraron triggers para la tabla orders\n";
}

echo "\n=== FIN VERIFICACIÓN ===\n";
