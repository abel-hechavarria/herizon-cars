<?php
/** Native Elementor widget registration. @package HerizonCars */
defined('ABSPATH') || exit;
add_action('elementor/elements/categories_registered', static function ($manager): void {
    $manager->add_category('herizon-cars', ['title' => 'Herizon Cars', 'icon' => 'fa fa-car']);
});
add_action('elementor/widgets/register', static function ($manager): void {
    require_once __DIR__ . '/../widgets/class-widget-base.php';
    foreach ([
        'logo-marquee' => 'Herizon_Cars_Widget_Logo_Marquee',
        'text-marquee' => 'Herizon_Cars_Widget_Text_Marquee',
        'elegant-gallery' => 'Herizon_Cars_Widget_Elegant_Gallery',
        'button' => 'Herizon_Cars_Widget_Button',
        'process' => 'Herizon_Cars_Widget_Process',
    ] as $file => $class) {
        require_once __DIR__ . '/../widgets/class-widget-' . $file . '.php';
        $manager->register(new $class());
    }
});
add_action('elementor/editor/after_enqueue_scripts', static function (): void {
    // Elementor can render and exit during init before our regular asset registration.
    herizon_cars_register_assets();
    wp_enqueue_script('herizon-cars-button-elementor');
});
