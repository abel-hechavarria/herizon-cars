<?php
/** Standalone contract checks for settings and public-view guards. */
define('ABSPATH', __DIR__ . '/');
$GLOBALS['hc_test_options'] = [];
$GLOBALS['hc_test_context'] = [];
function __($value, $domain = '') { return $value; }
function add_action(...$args) {}
function get_option($key, $default = null) { return $GLOBALS['hc_test_options'][$key] ?? $default; }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function sanitize_email($value) { return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : ''; }
function is_admin() { return !empty($GLOBALS['hc_test_context']['admin']); }
function wp_doing_ajax() { return !empty($GLOBALS['hc_test_context']['ajax']); }
function wp_is_json_request() { return !empty($GLOBALS['hc_test_context']['json']); }
function is_feed() { return !empty($GLOBALS['hc_test_context']['feed']); }
function is_embed() { return !empty($GLOBALS['hc_test_context']['embed']); }
require dirname(__DIR__) . '/inc/settings.php';
$checks = 0;
function hc_expect($condition, $message) {
    global $checks;
    $checks++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
$defaults = herizon_cars_settings();
hc_expect(count($defaults['modules']) === 15 && !in_array(false, $defaults['modules'], true), 'All requested module defaults enabled.');
hc_expect($defaults['cta']['phone'] === '' && $defaults['cta']['email'] === '' && $defaults['cta']['whatsapp'] === '', 'No personal contact defaults.');
hc_expect(!herizon_cars_module_enabled('unknown'), 'Unknown modules disabled.');
$input = ['modules' => ['preloader' => '1', 'cursor' => 'false', 'uploads' => ['invalid']], 'cta' => ['phone' => '+36 (20) 123-4567', 'whatsapp' => '+36 20 123 4567', 'email' => 'test@example.org', 'label' => '<b>Contact</b>'], 'selectors' => ['hero' => '.hc-hero', 'footer' => '<script>alert(1)</script>'], 'anchor_offset' => '9000'];
$output = herizon_cars_sanitize_settings($input);
hc_expect($output['modules']['preloader'] && !$output['modules']['cursor'] && !$output['modules']['uploads'], 'Checkboxes have strict boolean semantics.');
hc_expect(!$output['modules']['blur'], 'Unchecked module explicitly disabled.');
hc_expect($output['cta']['phone'] === '+36 (20) 123-4567' && $output['cta']['whatsapp'] === '36201234567', 'Contact values normalized.');
hc_expect($output['cta']['email'] === 'test@example.org' && $output['cta']['label'] === 'Contact', 'Email and text validated.');
hc_expect($output['anchor_offset'] === 1000, 'Offset clamped.');
$output = herizon_cars_sanitize_settings(['cta' => ['phone' => 'javascript:bad', 'whatsapp' => 'abc', 'email' => 'invalid', 'text' => []], 'selectors' => ['hero' => 'body{color:red}']]);
hc_expect($output['cta']['phone'] === '' && $output['cta']['whatsapp'] === '' && $output['cta']['email'] === '' && $output['cta']['text'] === '', 'Malformed channels and nested data rejected.');
hc_expect($output['selectors']['hero'] === '.hc-hero', 'CSS declarations rejected as selectors.');
$GLOBALS['hc_test_options']['herizon_cars_settings'] = ['modules' => ['cursor' => false]];
hc_expect(!herizon_cars_module_enabled('cursor') && herizon_cars_module_enabled('blur'), 'Partial existing options merge defaults.');
hc_expect(herizon_cars_is_public_view(), 'Normal frontend is public.');
foreach (['admin', 'ajax', 'json', 'feed', 'embed'] as $context) {
    $GLOBALS['hc_test_context'] = [$context => true];
    hc_expect(!herizon_cars_is_public_view(), 'Exclude ' . $context);
}
$GLOBALS['hc_test_context'] = [];
$_GET['elementor-preview'] = '123';
hc_expect(!herizon_cars_is_public_view(), 'Elementor preview excluded.');
unset($_GET['elementor-preview']);
echo "Settings tests: {$checks} passed\n";
