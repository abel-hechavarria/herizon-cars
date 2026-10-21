<?php
/** Register metadata after manifest-backed script/style registration. */
if (!defined('ABSPATH')) { exit; }
add_action('init', static function (): void {
    register_block_type(HERIZON_CARS_PATH . 'blocks/button');
}, 30);
