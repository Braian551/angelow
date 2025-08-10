-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-08-2025 a las 23:24:48
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `angelow`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `SearchProductsAndTerms` (IN `p_search_term` VARCHAR(255), IN `p_user_id` VARCHAR(20))   BEGIN
    -- Variables para manejar resultados
    DECLARE history_count INT;
    
    -- Resultados de productos coincidentes
    SELECT 
        p.id, 
        p.name, 
        p.slug, 
        pi.image_path
    FROM 
        products p
    LEFT JOIN 
        product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE 
        (p.name LIKE CONCAT('%', p_search_term, '%') OR p.description LIKE CONCAT('%', p_search_term, '%')) 
        AND p.is_active = 1
    LIMIT 5;
    
    -- Obtener términos de búsqueda del historial del usuario
    CREATE TEMPORARY TABLE IF NOT EXISTS temp_history_terms AS
    SELECT DISTINCT search_term 
    FROM search_history 
    WHERE user_id = p_user_id
    AND search_term LIKE CONCAT(p_search_term, '%')
    AND search_term IS NOT NULL
    AND search_term != ''
    ORDER BY created_at DESC
    LIMIT 6;
    
    -- Contar cuántos términos hay en el historial
    SELECT COUNT(*) INTO history_count FROM temp_history_terms;
    
    -- Si no hay suficientes términos, buscar en nombres de productos
    IF history_count < 4 THEN
        SELECT DISTINCT name 
        FROM products 
        WHERE name LIKE CONCAT('%', p_search_term, '%') AND is_active = 1
        LIMIT 4;
    ELSE
        SELECT * FROM temp_history_terms LIMIT 4;
    END IF;
    
    -- Limpiar tabla temporal
    DROP TEMPORARY TABLE IF EXISTS temp_history_terms;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `access_tokens`
--

CREATE TABLE `access_tokens` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `is_revoked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bulk_discount_rules`
--

CREATE TABLE `bulk_discount_rules` (
  `id` int(11) NOT NULL,
  `min_quantity` int(11) NOT NULL,
  `max_quantity` int(11) DEFAULT NULL,
  `discount_percentage` decimal(5,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `bulk_discount_rules`
--

INSERT INTO `bulk_discount_rules` (`id`, `min_quantity`, `max_quantity`, `discount_percentage`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 30, 50, 10.00, 1, '2025-07-27 15:50:11', '2025-07-27 15:50:11'),
(2, 51, NULL, 20.00, 1, '2025-07-27 15:50:38', '2025-07-27 15:50:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `session_id`, `created_at`, `updated_at`) VALUES
(3, '6861e06ddcf49', NULL, '2025-06-29 19:55:24', '2025-06-29 19:55:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `color_variant_id` int(11) DEFAULT NULL,
  `size_variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `color_variant_id`, `size_variant_id`, `quantity`, `created_at`, `updated_at`) VALUES
(17, 3, 61, 10, 19, 2, '2025-07-21 18:38:37', '2025-07-25 11:47:21'),
(19, 3, 61, 9, 17, 4, '2025-07-22 11:12:53', '2025-07-25 11:47:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`, `parent_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Vestidos', 'vestidos', 'Vestidos infantiles para ocasiones especiales', NULL, NULL, 1, '2025-06-21 21:11:42', '2025-06-21 21:11:42'),
(2, 'Conjuntos', 'conjuntos', 'Conjuntos de ropa coordinados', NULL, NULL, 1, '2025-06-21 21:11:42', '2025-06-21 21:11:42'),
(3, 'Pijamas', 'pijamas', 'Pijamas y ropa para dormir', NULL, NULL, 1, '2025-06-21 21:11:42', '2025-07-25 19:43:54'),
(4, 'Ropa Deportiva', 'ropa-deportiva', 'Ropa para actividades físicas', NULL, NULL, 1, '2025-06-21 21:11:42', '2025-06-21 21:11:42'),
(5, 'Accesorios', 'accesorios', 'Complementos y accesorios infantiles', NULL, NULL, 1, '2025-06-21 21:11:42', '2025-07-25 19:57:36'),
(6, 'Ropa Casual', 'ropa-casual', 'Ropa informal para el día a día', NULL, NULL, 1, '2025-06-21 21:11:42', '2025-06-21 21:11:42'),
(7, 'Ropa Formal', 'ropa-formal', 'Ropa para eventos especiales', NULL, NULL, 1, '2025-06-21 21:11:42', '2025-06-21 21:11:42'),
(8, 'Ropa de Baño', 'ropa-de-bano', 'Trajes de baño y ropa playera', NULL, NULL, 1, '2025-06-21 21:11:42', '2025-06-21 21:11:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `charity_organizations`
--

CREATE TABLE `charity_organizations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `charity_organizations`
--

INSERT INTO `charity_organizations` (`id`, `name`, `description`, `image`, `website`, `contact_email`, `contact_phone`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Fundación Ayuda Infantil', 'Organización dedicada a ayudar a niños en situación de vulnerabilidad', 'images/charities/children-foundation.jpg', NULL, NULL, NULL, 1, '2025-07-01 00:53:38', '2025-07-01 00:53:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `collections`
--

CREATE TABLE `collections` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `launch_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `collections`
--

INSERT INTO `collections` (`id`, `name`, `slug`, `description`, `image`, `launch_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Verano Mágico', 'verano-magico', 'Colección de verano con colores vibrantes y diseños frescos', NULL, '2025-05-01', 1, '2025-06-29 00:15:58', '2025-06-29 00:15:58'),
(2, 'Aventura Infantil', 'aventura-infantil', 'Ropa cómoda y resistente para pequeños exploradores', NULL, '2025-04-15', 1, '2025-06-29 00:15:58', '2025-06-29 00:15:58'),
(3, 'Dulces Sueños', 'dulces-suenos', 'Pijamas y ropa de dormir ultra suaves', NULL, '2025-03-20', 1, '2025-06-29 00:15:58', '2025-06-29 00:15:58'),
(4, 'Colección Clásica', 'coleccion-clasica', 'Diseños atemporales para ocasiones especiales', NULL, '2025-01-10', 1, '2025-06-29 00:15:58', '2025-06-29 00:15:58'),
(5, 'Mini Trendsetters', 'mini-trendsetters', 'Las últimas tendencias en moda infantil', NULL, '2025-06-01', 1, '2025-06-29 00:15:58', '2025-06-29 00:15:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `colombian_banks`
--

CREATE TABLE `colombian_banks` (
  `id` int(11) NOT NULL,
  `bank_code` varchar(10) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `colombian_banks`
--

INSERT INTO `colombian_banks` (`id`, `bank_code`, `bank_name`, `is_active`) VALUES
(1, '001', 'Banco de Bogotá', 1),
(2, '002', 'Banco Popular', 1),
(3, '006', 'Banco Santander', 1),
(4, '007', 'BBVA Colombia', 1),
(5, '009', 'Citibank', 1),
(6, '012', 'Banco GNB Sudameris', 1),
(7, '013', 'Banco AV Villas', 1),
(8, '014', 'Banco de Occidente', 1),
(9, '019', 'Bancoomeva', 1),
(10, '023', 'Banco Itaú', 1),
(11, '031', 'Bancolombia', 1),
(12, '032', 'Banco Caja Social', 1),
(13, '040', 'Banco Agrario de Colombia', 1),
(14, '051', 'Bancamía', 1),
(15, '052', 'Banco WWB', 1),
(16, '053', 'Banco Falabella', 1),
(17, '054', 'Banco Pichincha', 1),
(18, '058', 'Banco ProCredit', 1),
(19, '059', 'Banco Mundo Mujer', 1),
(20, '060', 'Banco Finandina', 1),
(21, '061', 'Bancoomeva S.A.', 1),
(22, '062', 'Banco Davivienda', 1),
(23, '063', 'Banco Cooperativo Coopcentral', 1),
(24, '065', 'Banco Santander', 1),
(25, '101', 'Nequi', 1),
(26, '102', 'Daviplata', 1),
(27, '103', 'Movii', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `colors`
--

CREATE TABLE `colors` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `hex_code` varchar(7) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `colors`
--

INSERT INTO `colors` (`id`, `name`, `hex_code`, `is_active`, `created_at`) VALUES
(1, 'Blanco', '#FFFFFF', 1, '2025-06-21 19:10:34'),
(2, 'Negro', '#000000', 1, '2025-06-21 19:10:34'),
(3, 'Rojo', '#FF0000', 1, '2025-06-21 19:10:34'),
(4, 'Azul', '#0000FF', 1, '2025-06-21 19:10:34'),
(5, 'Azul Marino', '#000080', 1, '2025-06-21 19:10:34'),
(6, 'Azul Cielo', '#87CEEB', 1, '2025-06-21 19:10:34'),
(7, 'Rosado', '#FFC0CB', 1, '2025-06-21 19:10:34'),
(8, 'Rosado Pastel', '#FFD1DC', 1, '2025-06-21 19:10:34'),
(9, 'Morado', '#800080', 1, '2025-06-21 19:10:34'),
(10, 'Lila', '#C8A2C8', 1, '2025-06-21 19:10:34'),
(11, 'Amarillo', '#FFFF00', 1, '2025-06-21 19:10:34'),
(12, 'Amarillo Pastel', '#FFFACD', 1, '2025-06-21 19:10:34'),
(13, 'Verde', '#008000', 1, '2025-06-21 19:10:34'),
(14, 'Verde Mentha', '#98FF98', 1, '2025-06-21 19:10:34'),
(15, 'Naranja', '#FFA500', 1, '2025-06-21 19:10:34'),
(16, 'Melón', '#FDBCB4', 1, '2025-06-21 19:10:34'),
(17, 'Gris', '#808080', 1, '2025-06-21 19:10:34'),
(18, 'Beige', '#F5F5DC', 1, '2025-06-21 19:10:34'),
(19, 'Café', '#A52A2A', 1, '2025-06-21 19:10:34'),
(20, 'Estampado', NULL, 1, '2025-06-21 19:10:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `delivery_cities`
--

CREATE TABLE `delivery_cities` (
  `id` int(11) NOT NULL,
  `city_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `delivery_cities`
--

INSERT INTO `delivery_cities` (`id`, `city_name`, `is_active`, `created_at`) VALUES
(1, 'Medellín', 1, '2025-06-30 09:13:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `discount_codes`
--

CREATE TABLE `discount_codes` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `discount_type_id` int(11) NOT NULL,
  `max_uses` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_single_use` tinyint(1) DEFAULT 0,
  `created_by` varchar(20) NOT NULL COMMENT 'ID del admin que lo creó',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `discount_code_products`
--

CREATE TABLE `discount_code_products` (
  `id` int(11) NOT NULL,
  `discount_code_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `discount_code_usage`
--

CREATE TABLE `discount_code_usage` (
  `id` int(11) NOT NULL,
  `discount_code_id` int(11) NOT NULL,
  `user_id` varchar(20) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `used_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `discount_types`
--

CREATE TABLE `discount_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `discount_types`
--

INSERT INTO `discount_types` (`id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Porcentaje', 'Descuento porcentual sobre el total', 1, '2025-07-27 17:38:50', '2025-07-27 17:38:50'),
(2, 'Monto fijo', 'Descuento de monto fijo', 1, '2025-07-27 17:38:50', '2025-07-27 17:38:50'),
(3, 'Envío gratis', 'Descuento para envío gratuito', 1, '2025-07-27 17:38:50', '2025-07-27 17:38:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `user_id` varchar(20) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('transferencia','contra_entrega','pse','efectivo') NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `is_anonymous` tinyint(1) DEFAULT 0,
  `message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `donation_campaigns`
--

CREATE TABLE `donation_campaigns` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `target_amount` decimal(10,2) DEFAULT NULL,
  `current_amount` decimal(10,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `donation_campaigns`
--

INSERT INTO `donation_campaigns` (`id`, `organization_id`, `title`, `slug`, `description`, `target_amount`, `current_amount`, `start_date`, `end_date`, `image`, `is_featured`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Educación para Niños Vulnerables', 'educacion-ninos-vulnerables', 'Campaña para recaudar fondos y proporcionar educación básica a niños en zonas rurales', 10000000.00, 2500000.00, '2025-07-01', '2025-07-31', 'images/campaigns/education-children.jpg', 1, 1, '2025-07-01 00:53:38', '2025-07-01 00:53:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `donation_transactions`
--

CREATE TABLE `donation_transactions` (
  `id` int(11) NOT NULL,
  `donation_id` int(11) NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `account_type` enum('ahorros','corriente') DEFAULT NULL,
  `account_holder` varchar(100) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fixed_amount_discounts`
--

CREATE TABLE `fixed_amount_discounts` (
  `id` int(11) NOT NULL,
  `discount_code_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `free_shipping_discounts`
--

CREATE TABLE `free_shipping_discounts` (
  `id` int(11) NOT NULL,
  `discount_code_id` int(11) NOT NULL,
  `shipping_method_id` int(11) DEFAULT NULL COMMENT 'NULL para todos los métodos'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `google_auth`
--

CREATE TABLE `google_auth` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `google_id` varchar(255) NOT NULL,
  `access_token` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `google_auth`
--

INSERT INTO `google_auth` (`id`, `user_id`, `google_id`, `access_token`, `created_at`) VALUES
(4, '6860007924a6a', '100021586628962750893', 'ya29.a0AS3H6NxWuxsKzvVZ78hlXIpNbkEuyBhlqCM-TAZVUOGequUWV3a07XgX6zOV1CBF5qW5qfR_7FaFucKHLluMZBfjTZw_MhKSVmbokJERwtzQbROc1a4BocIWIQ0ZL_W40z-KWYjh0I9SLbEtH3W2B_XqUlIn12l0fHRav4jESwaCgYKAZwSARESFQHGX2MihiMSealW_Ok6ItYSjWrP7Q0177', '2025-07-09 18:18:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `attempt_date`) VALUES
(1, 'braianoquen@gmail.com', '::1', '2025-07-20 11:18:27'),
(2, '3013636902', '::1', '2025-07-21 15:18:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `type_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `related_entity_type` enum('order','product','promotion','system','account') DEFAULT NULL,
  `related_entity_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_email_sent` tinyint(1) DEFAULT 0,
  `is_sms_sent` tinyint(1) DEFAULT 0,
  `is_push_sent` tinyint(1) DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `type_id` int(11) NOT NULL,
  `email_enabled` tinyint(1) DEFAULT 1,
  `sms_enabled` tinyint(1) DEFAULT 0,
  `push_enabled` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notification_queue`
--

CREATE TABLE `notification_queue` (
  `id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `channel` enum('email','sms','push') NOT NULL,
  `status` enum('pending','processing','sent','failed') DEFAULT 'pending',
  `attempts` tinyint(4) DEFAULT 0,
  `last_attempt_at` datetime DEFAULT NULL,
  `scheduled_at` datetime DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notification_types`
--

CREATE TABLE `notification_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `template` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `invoice_number` varchar(20) DEFAULT NULL,
  `user_id` varchar(20) DEFAULT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) DEFAULT 0.00,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `shipping_address` text DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `invoice_resolution` varchar(50) DEFAULT NULL COMMENT 'Resolución DIAN para facturación',
  `invoice_date` datetime DEFAULT NULL,
  `client_identification` varchar(20) DEFAULT NULL COMMENT 'Documento del cliente',
  `client_phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `color_variant_id` int(11) DEFAULT NULL,
  `size_variant_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `variant_name` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `user_id` varchar(20) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('transferencia','contra_entrega','pse','efectivo') NOT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `reference_number` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `account_type` enum('ahorros','corriente') DEFAULT NULL,
  `account_holder` varchar(100) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `payment_transactions`
--

INSERT INTO `payment_transactions` (`id`, `order_id`, `user_id`, `amount`, `payment_method`, `status`, `reference_number`, `bank_name`, `account_number`, `account_type`, `account_holder`, `payment_proof`, `notes`, `created_at`, `updated_at`) VALUES
(3, NULL, '6861e06ddcf49', 105000.00, 'transferencia', 'pending', '12313131', 'Banco Finandina', '1231331', 'ahorros', 'Braian Oquendo', 'uploads/payment_proofs/6863680e6fd16_braian-caricatura.jpg', 'adjfufdcusadhfi', '2025-06-30 23:46:06', '2025-06-30 23:46:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `percentage_discounts`
--

CREATE TABLE `percentage_discounts` (
  `id` int(11) NOT NULL,
  `discount_code_id` int(11) NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `max_discount_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `popular_searches`
--

CREATE TABLE `popular_searches` (
  `id` int(11) NOT NULL,
  `search_term` varchar(255) NOT NULL,
  `search_count` int(11) NOT NULL DEFAULT 1,
  `last_searched` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `popular_searches`
--

INSERT INTO `popular_searches` (`id`, `search_term`, `search_count`, `last_searched`) VALUES
(1, 'ropa deportiva de niños', 1, '2025-07-21 17:16:30'),
(2, 'ropa', 4, '2025-08-10 15:59:00'),
(4, 'Ropa deportiva', 2, '2025-08-10 15:59:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `gender` enum('niño','niña','bebe','unisex') NOT NULL DEFAULT 'unisex',
  `collection` varchar(50) DEFAULT NULL,
  `material` varchar(100) DEFAULT NULL,
  `care_instructions` text DEFAULT NULL,
  `compare_price` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `collection_id` int(11) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `products`
--

INSERT INTO `products` (`id`, `name`, `slug`, `description`, `brand`, `gender`, `collection`, `material`, `care_instructions`, `compare_price`, `price`, `category_id`, `collection_id`, `is_featured`, `is_active`, `created_at`, `updated_at`) VALUES
(61, 'Ropa deportiva', 'ropa-deportiva', '¡Comodidad, estilo y libertad de movimiento!\r\nEste conjunto deportivo de la Colección Clásica de Angelow es ideal para los pequeños que no paran. Fabricado en 100% algodón, ofrece frescura y suavidad, cuidando la piel de tu hijo durante todo el día.', 'angelow', 'niño', NULL, '100% algodon', 'Para conservar la calidad y durabilidad de esta prenda, se recomienda lavarla a máquina con agua fría y colores similares. No usar blanqueador, ya que puede dañar las fibras del algodón. Secar a baja temperatura o al aire libre para evitar el encogimiento. Si es necesario, planchar a temperatura media. No lavar en seco.', NULL, 35000.00, 4, 4, 0, 1, '2025-07-20 10:14:06', '2025-07-20 10:14:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_color_variants`
--

CREATE TABLE `product_color_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `color_id` int(11) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `product_color_variants`
--

INSERT INTO `product_color_variants` (`id`, `product_id`, `color_id`, `is_default`) VALUES
(9, 61, 2, 1),
(10, 61, 18, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `color_variant_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_primary` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `color_variant_id`, `image_path`, `alt_text`, `order`, `created_at`, `is_primary`) VALUES
(91, 61, NULL, 'uploads/productos/687d07be0095b_deportivo.jpg', 'Ropa deportiva - Imagen principal', 0, '2025-07-20 10:14:06', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_questions`
--

CREATE TABLE `product_questions` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `question` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `order_id` int(11) DEFAULT NULL COMMENT 'Para verificar compra',
  `rating` tinyint(1) NOT NULL COMMENT '1-5 estrellas',
  `title` varchar(100) NOT NULL,
  `comment` text NOT NULL,
  `images` text DEFAULT NULL COMMENT 'JSON de imágenes subidas',
  `is_verified` tinyint(1) DEFAULT 0 COMMENT 'Compra verificada',
  `is_approved` tinyint(1) DEFAULT 1 COMMENT 'Moderación',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_size_variants`
--

CREATE TABLE `product_size_variants` (
  `id` int(11) NOT NULL,
  `color_variant_id` int(11) NOT NULL,
  `size_id` int(11) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_price` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `product_size_variants`
--

INSERT INTO `product_size_variants` (`id`, `color_variant_id`, `size_id`, `sku`, `barcode`, `price`, `compare_price`, `quantity`, `is_active`) VALUES
(16, 9, 1, 'O', NULL, 35000.00, NULL, 10, 1),
(17, 9, 2, 'P', NULL, 35000.00, NULL, 20, 1),
(18, 10, 1, 'O', NULL, 35000.00, NULL, 20, 1),
(19, 10, 2, 'P', NULL, 35000.00, NULL, 10, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `question_answers`
--

CREATE TABLE `question_answers` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL COMMENT 'Puede ser vendedor o usuario',
  `answer` text NOT NULL,
  `is_seller` tinyint(1) DEFAULT 0 COMMENT '1=respuesta del vendedor',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `review_votes`
--

CREATE TABLE `review_votes` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `is_helpful` tinyint(1) NOT NULL COMMENT '1=útil, 0=no útil',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `search_history`
--

CREATE TABLE `search_history` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) DEFAULT NULL,
  `search_term` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `search_history`
--

INSERT INTO `search_history` (`id`, `user_id`, `search_term`, `created_at`) VALUES
(1, '6861e06ddcf49', 'da', '2025-07-17 19:11:43'),
(2, '6861e06ddcf49', 'ropa deportiva de niños', '2025-07-21 17:16:30'),
(3, '6861e06ddcf49', 'ropa', '2025-08-10 15:58:59'),
(4, '6861e06ddcf49', 'ropa', '2025-08-10 15:58:59'),
(5, '6861e06ddcf49', 'Ropa deportiva', '2025-08-10 15:59:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `shipping_price_rules`
--

CREATE TABLE `shipping_price_rules` (
  `id` int(11) NOT NULL,
  `min_price` decimal(10,2) NOT NULL,
  `max_price` decimal(10,2) DEFAULT NULL,
  `shipping_cost` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `shipping_price_rules`
--

INSERT INTO `shipping_price_rules` (`id`, `min_price`, `max_price`, `shipping_cost`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 50000.00, 100000.00, 8000.00, 1, '2025-07-27 15:17:16', '2025-07-27 15:21:17'),
(2, 0.00, 49999.00, 20000.00, 1, '2025-07-27 15:19:03', '2025-07-27 15:19:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sizes`
--

CREATE TABLE `sizes` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sizes`
--

INSERT INTO `sizes` (`id`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'XS', 'Extra Small', 1, '2025-06-21 20:47:14'),
(2, 'S', 'Small', 1, '2025-06-21 20:47:14'),
(3, 'M', 'Medium', 1, '2025-06-21 20:47:14'),
(4, 'L', 'Large', 1, '2025-06-21 20:47:14'),
(5, 'XL', 'Extra Large', 1, '2025-06-21 20:47:14'),
(6, 'XXL', 'Double Extra Large', 1, '2025-06-21 20:47:14'),
(7, '3XL', 'Triple Extra Large', 1, '2025-06-21 20:47:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock_history`
--

CREATE TABLE `stock_history` (
  `id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `previous_qty` int(11) NOT NULL,
  `new_qty` int(11) NOT NULL,
  `operation` enum('add','subtract','set','transfer_in','transfer_out') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `identification_type` enum('cc','ce','ti','pasaporte') NOT NULL DEFAULT 'cc',
  `identification_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `role` enum('customer','admin','delivery') DEFAULT 'customer',
  `is_blocked` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_access` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `identification_type`, `identification_number`, `password`, `image`, `role`, `is_blocked`, `created_at`, `updated_at`, `last_access`, `remember_token`, `token_expiry`) VALUES
('6860007924a6a', 'Braian', 'braianoquen@gmail.com', NULL, 'cc', NULL, '$2y$10$safkUgrODd3iixhDIq/y9eG7RnlUq.I3MAq3OsG4PXOsT7bZoss76', NULL, 'admin', 0, '2025-06-28 09:47:21', '2025-08-10 15:58:26', '2025-08-10 15:58:23', NULL, NULL),
('6861e06ddcf49', 'Braian', 'braianoquendurango@gmail.com', '3013636902', 'cc', '1023526011', '$2y$10$K5B1CBsezIVKb2osCQrgEuTwIr.JMvG2EVPUYZqIhS9yzuboS8prq', NULL, 'customer', 0, '2025-06-29 19:55:10', '2025-08-10 15:58:37', '2025-08-10 15:58:37', NULL, NULL),
('6862b7448112f', 'Juan', 'braianoquen2@gmail.com', NULL, 'cc', NULL, '$2y$10$lIkReeDLfMBHL7Mj2Vqrk.0LhoLlVboNNliNulgXzEiIrrexwMtrS', NULL, 'delivery', 0, '2025-06-30 11:11:48', '2025-06-30 11:25:30', '2025-06-30 11:25:30', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `address_type` enum('casa','apartamento','oficina','otro') NOT NULL DEFAULT 'casa',
  `alias` varchar(50) NOT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `recipient_phone` varchar(15) NOT NULL,
  `address` varchar(255) NOT NULL,
  `complement` varchar(100) DEFAULT NULL,
  `neighborhood` varchar(100) NOT NULL,
  `building_type` enum('casa','apartamento','edificio','conjunto','local') NOT NULL DEFAULT 'casa',
  `building_name` varchar(100) DEFAULT NULL,
  `apartment_number` varchar(20) DEFAULT NULL,
  `delivery_instructions` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `address_type`, `alias`, `recipient_name`, `recipient_phone`, `address`, `complement`, `neighborhood`, `building_type`, `building_name`, `apartment_number`, `delivery_instructions`, `is_default`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '6861e06ddcf49', 'casa', 'Hogar', 'Braian Oquendo', '3013636902', 'Cra 16D #57 B 162', NULL, 'Enciso', 'casa', NULL, NULL, 'llamar al llegar', 1, 1, '2025-07-12 22:25:54', '2025-07-22 14:57:41'),
(2, '6861e06ddcf49', 'oficina', 'Trabajo', 'Braian Oquendo', '3013636902', 'Cra 16D #57 B 163', 'Bloque 3', 'Belen', 'edificio', 'El miranda', '210', 'llamar antes de llegar', 0, 1, '2025-07-13 17:18:49', '2025-07-22 14:57:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `variant_images`
--

CREATE TABLE `variant_images` (
  `id` int(11) NOT NULL,
  `color_variant_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `order` int(11) DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `variant_images`
--

INSERT INTO `variant_images` (`id`, `color_variant_id`, `product_id`, `image_path`, `alt_text`, `order`, `is_primary`, `created_at`) VALUES
(26, 9, 61, 'uploads/productos/687d07be0106c_conjunto_niño2.jpg', 'Ropa deportiva - Imagen 1', 0, 1, '2025-07-20 10:14:06'),
(27, 9, 61, 'uploads/productos/687d07be015ec_deportivo.jpg', 'Ropa deportiva - Imagen 2', 1, 0, '2025-07-20 10:14:06'),
(28, 10, 61, 'uploads/productos/687d07be2aff9_coleccion primavera.jpg', 'Ropa deportiva - Imagen 1', 0, 1, '2025-07-20 10:14:06'),
(29, 10, 61, 'uploads/productos/687d07be3da3f_conjunto_niño.jpg', 'Ropa deportiva - Imagen 2', 1, 0, '2025-07-20 10:14:06'),
(30, 10, 61, 'uploads/productos/687d07be3e4d3_deportivo2.jpg', 'Ropa deportiva - Imagen 3', 2, 0, '2025-07-20 10:14:06'),
(31, 10, 61, 'uploads/productos/687d07be3ef1c_simba.jpg', 'Ropa deportiva - Imagen 4', 3, 0, '2025-07-20 10:14:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `access_tokens`
--
ALTER TABLE `access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `bulk_discount_rules`
--
ALTER TABLE `bulk_discount_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quantity_range` (`min_quantity`,`max_quantity`);

--
-- Indices de la tabla `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `color_variant_id` (`color_variant_id`),
  ADD KEY `size_variant_id` (`size_variant_id`);

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indices de la tabla `charity_organizations`
--
ALTER TABLE `charity_organizations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `collections`
--
ALTER TABLE `collections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indices de la tabla `colombian_banks`
--
ALTER TABLE `colombian_banks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bank_code` (`bank_code`);

--
-- Indices de la tabla `colors`
--
ALTER TABLE `colors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `delivery_cities`
--
ALTER TABLE `delivery_cities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `city_name` (`city_name`);

--
-- Indices de la tabla `discount_codes`
--
ALTER TABLE `discount_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `discount_type_id` (`discount_type_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `discount_code_products`
--
ALTER TABLE `discount_code_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `discount_product` (`discount_code_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `discount_code_usage`
--
ALTER TABLE `discount_code_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `discount_code_id` (`discount_code_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indices de la tabla `discount_types`
--
ALTER TABLE `discount_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `donation_campaigns`
--
ALTER TABLE `donation_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `organization_id` (`organization_id`);

--
-- Indices de la tabla `donation_transactions`
--
ALTER TABLE `donation_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `donation_id` (`donation_id`);

--
-- Indices de la tabla `fixed_amount_discounts`
--
ALTER TABLE `fixed_amount_discounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `discount_code_id` (`discount_code_id`);

--
-- Indices de la tabla `free_shipping_discounts`
--
ALTER TABLE `free_shipping_discounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `discount_code_id` (`discount_code_id`);

--
-- Indices de la tabla `google_auth`
--
ALTER TABLE `google_auth`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_date` (`attempt_date`);

--
-- Indices de la tabla `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_published` (`published_at`,`is_active`);

--
-- Indices de la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `related_entity` (`related_entity_type`,`related_entity_id`);

--
-- Indices de la tabla `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_type` (`user_id`,`type_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indices de la tabla `notification_queue`
--
ALTER TABLE `notification_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notification_id` (`notification_id`),
  ADD KEY `status` (`status`);

--
-- Indices de la tabla `notification_types`
--
ALTER TABLE `notification_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `color_variant_id` (`color_variant_id`),
  ADD KEY `size_variant_id` (`size_variant_id`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `percentage_discounts`
--
ALTER TABLE `percentage_discounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `discount_code_id` (`discount_code_id`);

--
-- Indices de la tabla `popular_searches`
--
ALTER TABLE `popular_searches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `search_term` (`search_term`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `fk_product_collection` (`collection_id`);

--
-- Indices de la tabla `product_color_variants`
--
ALTER TABLE `product_color_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `color_id` (`color_id`);

--
-- Indices de la tabla `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `color_variant_id` (`color_variant_id`);

--
-- Indices de la tabla `product_questions`
--
ALTER TABLE `product_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indices de la tabla `product_size_variants`
--
ALTER TABLE `product_size_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `color_variant_id` (`color_variant_id`),
  ADD KEY `size_id` (`size_id`);

--
-- Indices de la tabla `question_answers`
--
ALTER TABLE `question_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `review_votes`
--
ALTER TABLE `review_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_review` (`user_id`,`review_id`),
  ADD KEY `review_id` (`review_id`);

--
-- Indices de la tabla `search_history`
--
ALTER TABLE `search_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `shipping_price_rules`
--
ALTER TABLE `shipping_price_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_price_range` (`min_price`,`max_price`);

--
-- Indices de la tabla `sizes`
--
ALTER TABLE `sizes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `stock_history`
--
ALTER TABLE `stock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `variant_id` (`variant_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indices de la tabla `variant_images`
--
ALTER TABLE `variant_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `color_variant_id` (`color_variant_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `access_tokens`
--
ALTER TABLE `access_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `bulk_discount_rules`
--
ALTER TABLE `bulk_discount_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `charity_organizations`
--
ALTER TABLE `charity_organizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `collections`
--
ALTER TABLE `collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `colombian_banks`
--
ALTER TABLE `colombian_banks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `colors`
--
ALTER TABLE `colors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `delivery_cities`
--
ALTER TABLE `delivery_cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `discount_codes`
--
ALTER TABLE `discount_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `discount_code_products`
--
ALTER TABLE `discount_code_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `discount_code_usage`
--
ALTER TABLE `discount_code_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `discount_types`
--
ALTER TABLE `discount_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `donation_campaigns`
--
ALTER TABLE `donation_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `donation_transactions`
--
ALTER TABLE `donation_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fixed_amount_discounts`
--
ALTER TABLE `fixed_amount_discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `free_shipping_discounts`
--
ALTER TABLE `free_shipping_discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `google_auth`
--
ALTER TABLE `google_auth`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notification_queue`
--
ALTER TABLE `notification_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notification_types`
--
ALTER TABLE `notification_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `percentage_discounts`
--
ALTER TABLE `percentage_discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `popular_searches`
--
ALTER TABLE `popular_searches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT de la tabla `product_color_variants`
--
ALTER TABLE `product_color_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT de la tabla `product_questions`
--
ALTER TABLE `product_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `product_size_variants`
--
ALTER TABLE `product_size_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `question_answers`
--
ALTER TABLE `question_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `review_votes`
--
ALTER TABLE `review_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `search_history`
--
ALTER TABLE `search_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `shipping_price_rules`
--
ALTER TABLE `shipping_price_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `sizes`
--
ALTER TABLE `sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `stock_history`
--
ALTER TABLE `stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `variant_images`
--
ALTER TABLE `variant_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `access_tokens`
--
ALTER TABLE `access_tokens`
  ADD CONSTRAINT `access_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_3` FOREIGN KEY (`color_variant_id`) REFERENCES `product_color_variants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cart_items_ibfk_4` FOREIGN KEY (`size_variant_id`) REFERENCES `product_size_variants` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `discount_codes`
--
ALTER TABLE `discount_codes`
  ADD CONSTRAINT `discount_codes_ibfk_1` FOREIGN KEY (`discount_type_id`) REFERENCES `discount_types` (`id`),
  ADD CONSTRAINT `discount_codes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `discount_code_products`
--
ALTER TABLE `discount_code_products`
  ADD CONSTRAINT `discount_code_products_ibfk_1` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `discount_code_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `discount_code_usage`
--
ALTER TABLE `discount_code_usage`
  ADD CONSTRAINT `discount_code_usage_ibfk_1` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`),
  ADD CONSTRAINT `discount_code_usage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `discount_code_usage_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `donation_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `donation_campaigns`
--
ALTER TABLE `donation_campaigns`
  ADD CONSTRAINT `donation_campaigns_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `charity_organizations` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `donation_transactions`
--
ALTER TABLE `donation_transactions`
  ADD CONSTRAINT `donation_transactions_ibfk_1` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `fixed_amount_discounts`
--
ALTER TABLE `fixed_amount_discounts`
  ADD CONSTRAINT `fk_fixed_discount_code` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `free_shipping_discounts`
--
ALTER TABLE `free_shipping_discounts`
  ADD CONSTRAINT `fk_shipping_discount_code` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `google_auth`
--
ALTER TABLE `google_auth`
  ADD CONSTRAINT `google_auth_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `notification_types` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD CONSTRAINT `notification_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notification_preferences_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `notification_types` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notification_queue`
--
ALTER TABLE `notification_queue`
  ADD CONSTRAINT `notification_queue_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`color_variant_id`) REFERENCES `product_color_variants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_items_ibfk_4` FOREIGN KEY (`size_variant_id`) REFERENCES `product_size_variants` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payment_transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `percentage_discounts`
--
ALTER TABLE `percentage_discounts`
  ADD CONSTRAINT `fk_percentage_discount_code` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_collection` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`),
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `product_color_variants`
--
ALTER TABLE `product_color_variants`
  ADD CONSTRAINT `product_color_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_color_variants_ibfk_2` FOREIGN KEY (`color_id`) REFERENCES `colors` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_images_ibfk_2` FOREIGN KEY (`color_variant_id`) REFERENCES `product_color_variants` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `product_size_variants`
--
ALTER TABLE `product_size_variants`
  ADD CONSTRAINT `product_size_variants_ibfk_1` FOREIGN KEY (`color_variant_id`) REFERENCES `product_color_variants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_size_variants_ibfk_2` FOREIGN KEY (`size_id`) REFERENCES `sizes` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `search_history`
--
ALTER TABLE `search_history`
  ADD CONSTRAINT `search_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `fk_user_addresses_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `variant_images`
--
ALTER TABLE `variant_images`
  ADD CONSTRAINT `variant_images_ibfk_1` FOREIGN KEY (`color_variant_id`) REFERENCES `product_color_variants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `variant_images_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
