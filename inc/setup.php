<?php
/** Child-specific setup; Hello retains core theme support and locations. */
if (!defined('ABSPATH')) { exit; }
add_action('after_setup_theme', static function (): void {
    load_child_theme_textdomain('herizon-cars', HERIZON_CARS_PATH . 'languages');
});
