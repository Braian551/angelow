<?php
/**
 * Script principal de migración
 * Redirige a la migración específica del módulo de orders_badge
 * 
 * @author Angelow System
 * @date 2025-10-12
 */

// Redireccionar a la migración específica
header('Location: migrations/orders_badge/run_migration.php');
exit();
