<?php
/** Explicit theme bootstrap. @package HerizonCars */
if (!defined('ABSPATH')) { exit; }
define('HERIZON_CARS_VERSION', '1.2.0');
define('HERIZON_CARS_PATH', trailingslashit(get_stylesheet_directory()));
define('HERIZON_CARS_URL', trailingslashit(get_stylesheet_directory_uri()));
foreach (['setup', 'settings', 'components/button', 'assets', 'modules', 'integrations/elementor', 'integrations/github-updates', 'blocks/button'] as $module) {
    require_once HERIZON_CARS_PATH . 'inc/' . $module . '.php';
}
