<?php
/** Explicit module registry and component rendering. @package HerizonCars */
defined('ABSPATH') || exit;
function herizon_cars_register_modules(): void {
    static $registered = false;
    if ($registered) {
        return;
    }
    $registered = true;
    $modules = [
        ['file' => 'uploads.php', 'register' => 'herizon_cars_register_uploads', 'requires' => ['uploads']],
        ['file' => 'utilities.php', 'register' => 'herizon_cars_register_utilities', 'requires' => []],
    ];
    foreach ($modules as $module) {
        foreach ($module['requires'] as $required) {
            if (!herizon_cars_module_enabled($required)) {
                continue 2;
            }
        }
        $file = __DIR__ . '/modules/' . $module['file'];
        if (is_readable($file)) {
            require_once $file;
            if (is_callable($module['register'])) {
                call_user_func($module['register']);
            }
        }
    }
}
add_action('after_setup_theme', 'herizon_cars_register_modules', 20);

function herizon_cars_render_preloader(): void {
    static $rendered = false;
    if ($rendered || !herizon_cars_is_public_view() || !herizon_cars_module_enabled('preloader')) {
        return;
    }
    $rendered = true;
    get_template_part('template-parts/components/preloader', null, ['text' => herizon_cars_settings()['preloader_text']]);
}
add_action('wp_body_open', 'herizon_cars_render_preloader', 1);
add_action('wp_footer', static function (): void {
    if (!herizon_cars_is_public_view()) {
        return;
    }
    // Some page templates omit wp_body_open. Keep the same idempotent renderer.
    herizon_cars_render_preloader();
    foreach (['blur', 'cursor', 'back_to_top', 'sticky_cta'] as $module) {
        if (herizon_cars_module_enabled($module)) {
            get_template_part('template-parts/components/' . str_replace('_', '-', $module), null, ['settings' => herizon_cars_settings()]);
        }
    }
}, 5);
