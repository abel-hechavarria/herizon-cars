<?php
/** Native dynamic rendering; saved fallback remains usable without the theme. */
if (!defined('ABSPATH')) { exit; }
$attributes['className'] = trim('wp-block-herizon-cars-button ' . ($attributes['className'] ?? ''));
echo herizon_cars_button_render($attributes);
