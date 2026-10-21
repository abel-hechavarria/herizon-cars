<?php
/** Standalone regressions. Run: php -n tests/php/run.php */
error_reporting(E_ALL);
set_error_handler(static function ($level, $message, $file, $line) {
    if (error_reporting() & $level) {
        throw new ErrorException($message, 0, $level, $file, $line);
    }
    return false;
});
define('ABSPATH', __DIR__ . '/');
$GLOBALS['hc_capabilities'] = ['manage_options' => true, 'upload_files' => true];
$GLOBALS['hc_upload_limit'] = 5 * 1024 * 1024;
$GLOBALS['hc_hooks'] = [];
$GLOBALS['hc_enabled'] = [];
function __($value, $domain = '') { return $value; }
function current_user_can($capability) { return ! empty($GLOBALS['hc_capabilities'][$capability]); }
function wp_max_upload_size() { return $GLOBALS['hc_upload_limit']; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function home_url($path = '') { return 'https://cars.example' . $path; }
function add_action($hook, $callback, $priority = 10, $args = 1) { $GLOBALS['hc_hooks'][] = ['action', $hook, $callback, $priority]; }
function add_filter($hook, $callback, $priority = 10, $args = 1) { $GLOBALS['hc_hooks'][] = ['filter', $hook, $callback, $priority]; }
function remove_action($hook, $callback, $priority = 10) { $GLOBALS['hc_hooks'][] = ['remove', $hook, $callback, $priority]; }
function add_shortcode($name, $callback) { $GLOBALS['hc_hooks'][] = ['shortcode', $name, $callback]; }
function herizon_cars_module_enabled($key) { return ! empty($GLOBALS['hc_enabled'][$key]); }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function wp_date($format) { return '2026'; }
function is_ssl() { return $GLOBALS['hc_ssl'] ?? true; }
function is_admin() { return $GLOBALS['hc_admin'] ?? false; }
function wp_doing_ajax() { return $GLOBALS['hc_ajax'] ?? false; }
function wp_is_json_request() { return $GLOBALS['hc_json'] ?? false; }
function is_feed() { return $GLOBALS['hc_feed'] ?? false; }
function is_trackback() { return false; }
function is_robots() { return false; }
function is_front_page() { return true; }
function wp_scripts() { return $GLOBALS['hc_scripts']; }
function wp_styles() { return $GLOBALS['hc_styles']; }
function wp_dequeue_script($handle) { $GLOBALS['hc_script_removed'][] = $handle; }
function wp_dequeue_style($handle) { $GLOBALS['hc_style_removed'][] = $handle; }
class WP_Post { public $post_name = 'our-cars'; public $post_type = 'page'; }
function is_singular() { return $GLOBALS['hc_singular'] ?? true; }
function get_queried_object() { return $GLOBALS['hc_post']; }
function sanitize_html_class($value) { return preg_replace('/[^A-Za-z0-9_-]/', '', $value); }
require dirname(__DIR__, 2) . '/inc/modules/uploads.php';
require dirname(__DIR__, 2) . '/inc/modules/utilities.php';
$passed = 0;
function check($condition, string $message): void {
    if (! $condition) { throw new RuntimeException($message); }
    ++$GLOBALS['passed'];
}
function temporary_upload(string $name, string $content): array {
    $temporary = tempnam(sys_get_temp_dir(), 'hc-test-');
    file_put_contents($temporary, $content);
    $GLOBALS['hc_temp_files'][] = $temporary;
    return ['name' => $name, 'tmp_name' => $temporary, 'error' => 0, 'type' => 'application/octet-stream', 'size' => strlen($content)];
}
register_shutdown_function(static function () {
    foreach ($GLOBALS['hc_temp_files'] ?? [] as $file) { if (is_file($file)) { unlink($file); } }
});
$svg_start = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">';
$basic = $svg_start . '<path d="M0 0h20v20z" fill="#123456"/></svg>';
check(str_contains(herizon_cars_sanitize_svg($basic), 'viewBox="0 0 20 20"'), 'Valid SVG geometry is retained');
check(herizon_cars_sanitize_svg('<svg>') === null, 'Malformed XML is rejected');
check(herizon_cars_sanitize_svg('<svg/>') === null, 'Root requires SVG namespace');
check(herizon_cars_sanitize_svg('<svg xmlns="https://evil.example"/>') === null, 'Foreign root namespace rejected');
check(herizon_cars_sanitize_svg('<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>' . $basic) === null, 'DTD and external entities rejected');
check(herizon_cars_sanitize_svg('<!DOCTYPE svg SYSTEM "https://evil.example/test.dtd">' . $basic) === null, 'External DTD rejected');
$malicious = $svg_start . '<script>alert(1)</script><foreignObject><div>evil</div></foreignObject><style>svg{fill:red}</style><a href="https://evil.example">bad</a><image href="https://evil.example"/><animate attributeName="href"/><path id="safe" onload="alert(1)" style="fill:url(https://evil.example)" fill="url(https://evil.example/a)" stroke="url(&#x68;ttps://evil.example)"/><use href="javascript:alert(1)"/></svg>';
$clean = herizon_cars_sanitize_svg($malicious);
foreach (['script', 'foreignObject', '<style', '<image', '<animate', 'onload', 'style=', 'evil.example', 'javascript:'] as $danger) {
    check(! str_contains($clean, $danger), 'Active SVG content removed: ' . $danger);
}
$namespace = $svg_start . '<g xmlns:x="https://evil.example"><x:path d="M1 1"/><path x:href="#good" x:onload="alert(1)" id="good"/><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#good"/></g></svg>';
$clean = herizon_cars_sanitize_svg($namespace);
check(! str_contains($clean, 'evil.example') && ! str_contains($clean, 'onload'), 'Foreign attributes and namespace declarations eliminated');
check(str_contains($clean, 'href="#good"'), 'Safe xlink fragment normalized to SVG href');
$clean = herizon_cars_sanitize_svg($svg_start . '<g xml:base="https://evil.example/"><use href="#target"/><?php echo 1; ?><path id="target"/></g></svg>');
check(! str_contains($clean, 'xml:base') && ! str_contains($clean, '<?php') && ! str_contains($clean, 'evil.example'), 'XML base and embedded processing instructions removed');
$references = $svg_start . '<defs><linearGradient id="gradient"><stop offset="0" stop-color="red"/></linearGradient></defs><path fill="url(#gradient)"/><use href="#missing"/><path stroke="url(#missing)"/><use href="data:image/svg+xml,xx"/><g id="gradient"/></svg>';
$clean = herizon_cars_sanitize_svg($references);
check(str_contains($clean, 'fill="url(#gradient)"'), 'Existing internal paint reference kept');
check(! str_contains($clean, '#missing') && ! str_contains($clean, 'data:'), 'Dangling and data references removed');
check(substr_count($clean, 'id="gradient"') === 1, 'Duplicate IDs removed');
$clean = herizon_cars_sanitize_svg('<?xml-stylesheet href="https://evil.example/style.css"?>' . $basic);
check(! str_contains($clean, 'xml-stylesheet'), 'XML processing instructions removed');
$clean = herizon_cars_sanitize_svg($svg_start . '<path fill="u\\rl(https://evil.example)" stroke="expression(alert(1))"/><path fill="rgb(1,2,3)"/></svg>');
check(! str_contains($clean, 'evil.example') && ! str_contains($clean, 'expression'), 'Escaped CSS function and expression rejected');
check(str_contains($clean, 'rgb(1,2,3)'), 'Simple color function retained');
$uploaded = temporary_upload('Logo.SVG', $malicious);
$result = herizon_cars_upload_prefilter($uploaded);
check(empty($result['error']) && $result['name'] === 'Logo.svg' && $result['type'] === 'image/svg+xml', 'SVG upload normalized');
check(! str_contains(file_get_contents($uploaded['tmp_name']), '<script'), 'Temporary SVG sanitized before movement');
$compressed = temporary_upload('logo.svgz', gzencode($basic));
$result = herizon_cars_upload_prefilter($compressed);
check(empty($result['error']) && $result['name'] === 'logo.svg', 'SVGZ normalized to SVG extension');
check(str_starts_with(file_get_contents($result['tmp_name']), '<svg'), 'SVGZ stored uncompressed and sanitized');
check(! empty(herizon_cars_upload_prefilter(temporary_upload('bad.svgz', 'not gzip'))['error']), 'Corrupt gzip rejected');
$GLOBALS['hc_upload_limit'] = 256;
check(! empty(herizon_cars_upload_prefilter(temporary_upload('bomb.svgz', gzencode(str_repeat('A', 1000))))['error']), 'Oversized gzip expansion rejected');
check(! empty(herizon_cars_upload_prefilter(temporary_upload('large.svg', str_repeat('A', 300)))['error']), 'Raw size bounded by WP upload maximum');
$GLOBALS['hc_upload_limit'] = 20 * 1024 * 1024;
check(herizon_cars_upload_limit() === 5 * 1024 * 1024, 'Absolute decompression cap is 5 MiB');
check(empty(herizon_cars_upload_prefilter(temporary_upload('lottie.json', '{"v":"5.7","layers":[]}'))['error']), 'Valid JSON accepted');
check(empty(herizon_cars_upload_prefilter(temporary_upload('null.json', 'null'))['error']), 'JSON scalar null accepted');
check(! empty(herizon_cars_upload_prefilter(temporary_upload('bad.json', '{broken'))['error']), 'Malformed JSON rejected');
check(! empty(herizon_cars_upload_prefilter(temporary_upload('bad.json', "{\"a\":\"\xFF\"}"))['error']), 'Non-UTF8 JSON rejected');
$spoof = temporary_upload('logo.txt', $basic); $spoof['type'] = 'image/svg+xml';
check(! empty(herizon_cars_upload_prefilter($spoof)['error']), 'MIME and extension mismatch rejected');
foreach (['manage_options', 'upload_files'] as $capability) {
    $GLOBALS['hc_capabilities'][$capability] = false;
    $mimes = herizon_cars_upload_mimes(['svg|svgz' => 'image/svg+xml', 'json' => 'application/json', 'jpg' => 'image/jpeg']);
    check($mimes === ['jpg' => 'image/jpeg'], 'Unprivileged MIME grants stripped: ' . $capability);
    check(! empty(herizon_cars_upload_prefilter(temporary_upload('denied.svg', $basic))['error']), 'Prefilter enforces capability: ' . $capability);
    $candidate = temporary_upload('denied.json', '{}');
    check(herizon_cars_upload_filetype(['ext' => 'json', 'type' => 'application/json'], $candidate['tmp_name'], 'denied.json')['type'] === false, 'Filetype guard enforces capability: ' . $capability);
    $GLOBALS['hc_capabilities'][$capability] = true;
}
$candidate = temporary_upload('direct.svg', $malicious);
check(herizon_cars_upload_filetype([], $candidate['tmp_name'], 'direct.svg')['type'] === 'image/svg+xml', 'Direct filetype path validates SVG');
check(! str_contains(file_get_contents($candidate['tmp_name']), '<script'), 'Direct filetype path sanitizes temporary file');
$candidate = temporary_upload('bad.svg', '<svg>');
check(herizon_cars_upload_filetype(['type' => 'image/svg+xml'], $candidate['tmp_name'], 'bad.svg')['type'] === false, 'Direct path fails closed on XML');
check(isset(herizon_cars_validate_media_file('/file/does/not/exist', 'missing.svg')['error']), 'Unreadable source fails closed');

$input = '<img src="http://cars.example/a.jpg"><a href="http://cars.example?x=1">x</a><script>{"u":"http:\\/\\/cars.example\\/logo.svg"}</script>';
$output = herizon_cars_normalize_http_urls($input);
check(substr_count($output, 'https:') === 3 && ! str_contains($output, '"http:'), 'Ordinary and JSON escaped URLs normalized');
foreach (['http://cars.example.evil/a', 'http://other.example/a', 'http://sub.cars.example/a', 'http://cars.example@evil.example/a', 'http://evil@cars.example/a', 'http://cars.example:bad/a', 'prefixhttp://cars.example/a'] as $url) {
    check(herizon_cars_normalize_http_urls($url) === $url, 'Unsafe/different authority unchanged: ' . $url);
}
check(herizon_cars_normalize_http_urls('HTTP://CARS.EXAMPLE/a') === 'https://CARS.EXAMPLE/a', 'Host and scheme comparison case insensitive');
check(herizon_cars_normalize_http_urls('http://cars.example:8080/a') === 'https://cars.example:8080/a', 'Explicit port preserved');
check(herizon_cars_is_html_response(['Content-Type: text/html; charset=UTF-8'], 200), 'HTML response eligible');
foreach ([[['Content-Type: application/json'],200], [['Content-Type: text/html', 'Content-Disposition: attachment; filename=x.html'],200], [['Content-Type: text/html', 'Content-Encoding: gzip'],200], [['Content-Type: text/html'],302], [['Content-Type: text/html'],206], [[],200]] as [$headers, $status]) {
    check(! herizon_cars_is_html_response($headers, $status), 'Non-HTML/download/status/compressed response excluded');
}
foreach (['hc_admin', 'hc_ajax', 'hc_json', 'hc_feed'] as $flag) {
    $GLOBALS[$flag] = true;
    check(! herizon_cars_public_html_request(), 'Request excluded: ' . $flag);
    $GLOBALS[$flag] = false;
}
$GLOBALS['hc_ssl'] = false; check(! herizon_cars_public_html_request(), 'HTTP request excluded'); $GLOBALS['hc_ssl'] = true;
$_GET['elementor-preview'] = '1'; check(! herizon_cars_public_html_request(), 'Elementor preview excluded'); unset($_GET['elementor-preview']);
$registry = (object) ['queue' => ['jquery-ui-core', 'elementor'], 'done' => [], 'to_do' => [], 'registered' => ['elementor' => (object) ['deps' => ['widget']], 'widget' => (object) ['deps' => ['jquery-ui-core']]]];
check(herizon_cars_dependency_is_required($registry, 'jquery-ui-core'), 'Transitive dependency preserved');
$registry->queue = ['jquery-ui-core']; check(! herizon_cars_dependency_is_required($registry, 'jquery-ui-core'), 'Standalone unused UI removable');
$registry->done = ['elementor']; check(herizon_cars_dependency_is_required($registry, 'jquery-ui-core'), 'Already printed dependent protected');
$registry->done = []; $registry->queue = ['a']; $registry->registered = ['a' => (object) ['deps' => ['b']], 'b' => (object) ['deps' => ['a']]];
check(! herizon_cars_dependency_is_required($registry, 'jquery-ui-core'), 'Dependency cycle terminates');
$GLOBALS['hc_scripts'] = (object) ['queue' => ['jquery-ui-core'], 'done' => [], 'registered' => []];
$GLOBALS['hc_styles'] = (object) ['queue' => ['jquery-ui-core', 'custom'], 'done' => [], 'registered' => ['custom' => (object) ['deps' => ['jquery-ui-core']]]];
herizon_cars_dequeue_unused_jquery_ui();
check($GLOBALS['hc_script_removed'] === ['jquery-ui-core'], 'Unused script dequeued');
check(empty($GLOBALS['hc_style_removed']), 'Dependent style preserved');
check(herizon_cars_image_sizes(['thumbnail' => [], 'medium_large' => [], '1536x1536' => [], '2048x2048' => []]) === ['thumbnail' => []], 'Only requested image sizes removed');
check(herizon_cars_big_image_threshold() === 2560, 'Big-image threshold retained');
$links = ['http://cars.example/a', 'https://CARS.EXAMPLE/b', 'https://cars.example.evil/c', 'https://other.example/'];
herizon_cars_remove_self_pingbacks($links);
check(array_values($links) === ['https://cars.example.evil/c', 'https://other.example/'], 'Pingbacks compare parsed exact hosts');
$GLOBALS['hc_post'] = new WP_Post();
check(herizon_cars_slug_body_class(['existing']) === ['existing', 'page-our-cars'], 'Singular slug class added');
$GLOBALS['hc_singular'] = false; check(herizon_cars_slug_body_class(['existing']) === ['existing'], 'Archive avoids leaked last-post slug');
check(herizon_cars_year_shortcode() === '2026', 'Shortcode uses WP timezone API');
herizon_cars_register_uploads(); herizon_cars_register_utilities();
check(empty($GLOBALS['hc_hooks']), 'Disabled modules register no hooks');
foreach (['uploads', 'https_normalization', 'jquery_ui', 'image_sizes', 'shortlinks', 'self_pingbacks', 'body_class', 'site_icon', 'year'] as $module) { $GLOBALS['hc_enabled'][$module] = true; }
herizon_cars_register_uploads(); herizon_cars_register_utilities();
check(in_array(['filter', 'wp_handle_upload_prefilter', 'herizon_cars_upload_prefilter', 100], $GLOBALS['hc_hooks'], true), 'Upload prefilter registered');
check(in_array(['filter', 'wp_handle_sideload_prefilter', 'herizon_cars_upload_prefilter', 100], $GLOBALS['hc_hooks'], true), 'Sideload prefilter registered');
check(in_array(['shortcode', 'ahj_year', 'herizon_cars_year_shortcode'], $GLOBALS['hc_hooks'], true) && in_array(['shortcode', 'herizon_year', 'herizon_cars_year_shortcode'], $GLOBALS['hc_hooks'], true), 'Year alias and canonical shortcode registered');
echo 'PASS: ' . $passed . " technical PHP assertions\n";
