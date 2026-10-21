<?php
/** Run in separate PHP processes with disable_classes/functions INI flags. */
define('ABSPATH', __DIR__ . '/');
function current_user_can($capability) { return true; }
function wp_max_upload_size() { return 5 * 1024 * 1024; }
function __($value, $domain = '') { return $value; }
require dirname(__DIR__, 2) . '/inc/modules/uploads.php';
$svg = '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0"/></svg>';
$mode = $argv[1] ?? '';
if ($mode === 'dom') {
    if (method_exists('DOMDocument', 'loadXML')) {
        echo "SKIP: this PHP runtime does not disable the DOMDocument class\n";
        exit(0);
    }
    if (herizon_cars_sanitize_svg($svg) !== null) {
        throw new RuntimeException('Missing DOMDocument must reject SVG');
    }
    echo "PASS: missing DOMDocument rejects SVG\n";
} elseif ($mode === 'gzip') {
    $temporary = tempnam(sys_get_temp_dir(), 'hc-deps-');
    file_put_contents($temporary, gzencode($svg));
    try {
        if (function_exists('gzdecode') || ! isset(herizon_cars_validate_media_file($temporary, 'logo.svgz')['error'])) {
            throw new RuntimeException('Missing gzdecode must reject SVGZ');
        }
    } finally {
        unlink($temporary);
    }
    echo "PASS: missing gzdecode rejects SVGZ\n";
} else {
    throw new RuntimeException('Choose dom or gzip mode with the corresponding disabled PHP dependency');
}
