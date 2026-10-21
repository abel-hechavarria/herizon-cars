<?php
/** Shared rendering/security contract tests, independent of a live database. */
define('ABSPATH', __DIR__ . '/');
define('HERIZON_CARS_PATH', dirname(__DIR__, 2) . '/');
function __($text, $domain = '') { return $text; }
function sanitize_text_field($text) { return trim(strip_tags($text)); }
function sanitize_html_class($text) { return preg_replace('/[^a-zA-Z0-9_-]/', '', $text); }
function sanitize_title($text) { return preg_replace('/[^a-zA-Z0-9_-]/', '-', $text); }
function absint($value) { return abs((int) $value); }
function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function esc_html($value) { return esc_attr($value); }
function esc_url_raw($url, $protocols = []) { $scheme = parse_url($url, PHP_URL_SCHEME); return !$scheme || in_array(strtolower($scheme), $protocols, true) ? $url : ''; }
function esc_url($url, $protocols = []) { return esc_attr(esc_url_raw($url, $protocols)); }
function wp_enqueue_style($handle) {}
function get_post_type($id) { return $id === 44 ? 'attachment' : false; }
function get_post_mime_type($id) { return 'image/svg+xml'; }
function get_attached_file($id) { return $GLOBALS['button_svg_file']; }
function wp_max_upload_size() { return 10 * 1024 * 1024; }
require HERIZON_CARS_PATH . 'inc/components/button.php';
$count = 0;
function expect_button($condition, $message) { global $count; $count++; if (!$condition) { throw new RuntimeException($message); } }
$schema = herizon_cars_button_schema();
expect_button(count($schema['presets']) === 3, 'Three shared presets.');
expect_button(count($schema['fields']) === 30, 'Complete shared style contract.');
foreach (['fas:play', 'fas:house', 'fas:carrot'] as $icon) {
    $html = herizon_cars_button_render(['text' => 'Example', 'url' => '#section', 'icon' => $icon]);
    expect_button(str_contains($html, '<svg') && str_contains($html, 'href="#section"'), $icon . ' renders an accessible anchor.');
    expect_button(str_contains($html, 'aria-hidden="true"') && str_contains($html, 'focusable="false"'), 'Decorative icon.');
}
foreach (['pill', 'outline', 'solid'] as $variant) {
    $normal = ['textColor' => '#123456', 'paddingTop' => '16px', 'background' => 'linear-gradient(90deg, #fff 0%, #000 100%)'];
    $attrs = ['variant' => $variant, 'text' => 'My text', 'url' => 'https://example.test', 'newTab' => true, 'styles' => ['normal' => ['desktop' => $normal, 'mobile' => ['gap' => '8px']], 'hover' => ['tablet' => ['iconColor' => 'rgba(0, 0, 0, .5)']]]];
    $html = herizon_cars_button_render($attrs);
    expect_button(str_contains($html, 'hc-button--' . $variant), 'Preset class ' . $variant);
    expect_button(str_contains($html, '--hc-button-text-color:#123456'), 'Explicit style survives preset changes.');
    expect_button(str_contains($html, '--hc-button-mobile-gap:8px') && str_contains($html, '--hc-button-tablet-hover-icon-color:'), 'Device/state CSS custom properties.');
    expect_button(str_contains($html, 'rel="noopener noreferrer"'), 'Safe new tab.');
}
foreach (['', '#', 'javascript:alert(1)', 'data:text/html,<svg/>', "https://example.test/\nwrong"] as $bad_url) {
    $html = herizon_cars_button_render(['url' => $bad_url, 'iconEnabled' => false]);
    expect_button(!str_contains($html, '<a ') && !str_contains($html, 'href='), 'Non-navigable for invalid URL.');
}
$html = herizon_cars_button_render(['text' => '<img src=x onerror=alert(1)>Hello', 'ariaLabel' => '" onclick="bad', 'iconEnabled' => false, 'url' => '#target']);
expect_button(!str_contains($html, '<img') && !str_contains($html, '<svg'), 'No raw markup or hidden icon markup.');
expect_button(str_contains($html, '&quot; onclick=&quot;bad'), 'Attribute output escaped.');
$style = ['textColor' => 'red;position:fixed', 'background' => 'url(https://external.test/x)', 'fontFamily' => 'x</style>', 'shadow' => '0 0 0 red;display:none', 'paddingTop' => '-3px', 'lineHeight' => '99', 'invalidField' => '#000'];
$clean = herizon_cars_button_normalize(['styles' => ['normal' => ['desktop' => $style]], 'duration' => 99999, 'hoverMove' => -100, 'icon' => '</svg>bad', 'variant' => []]);
expect_button(empty($clean['styles']), 'CSS declarations/resources/unknown fields rejected.');
expect_button($clean['duration'] === 2000 && $clean['hoverMove'] === -30 && $clean['icon'] === 'fas:play' && $clean['variant'] === 'pill', 'Scalar boundaries and icon fallback.');
$map = array_column($schema['fields'], null, 'key');
foreach (['#fff', '#ffff', '#AABBCCDD', 'transparent', 'rgba(1,2,3,.5)', 'var(--e-global-color-primary)'] as $value) { expect_button(herizon_cars_button_style_value($map['textColor'], $value) !== null, 'Valid color ' . $value); }
expect_button(herizon_cars_button_style_value($map['textColor'], 'var(--untrusted-resource)') === null, 'Only kit color references allowed.');
foreach (['16px', '1.2rem', '2em', '100%', '5vw', '0'] as $value) { expect_button(herizon_cars_button_style_value($map['gap'], $value) !== null, 'Valid length ' . $value); }
expect_button(herizon_cars_button_style_value($map['width'], 'auto') === 'auto', 'Auto width.');
expect_button(herizon_cars_button_style_value($map['letterSpacing'], '-.5px') !== null, 'Negative tracking allowed.');
expect_button(herizon_cars_button_style_value($map['background'], 'linear-gradient(red,blue);color:red') === null, 'Gradient cannot escape value context.');
$GLOBALS['button_svg_file'] = tempnam(sys_get_temp_dir(), 'hc-button-test-');
file_put_contents($GLOBALS['button_svg_file'], '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" onload="bad()"><script>bad()</script><path fill="#fff" d="M0 0L10 10"/></svg>');
$html = herizon_cars_button_render(['text' => 'SVG', 'url' => '#test', 'iconSource' => 'media', 'iconId' => 44]);
expect_button(str_contains($html, '<svg') && !str_contains($html, 'script') && !str_contains($html, 'onload'), 'Custom SVG re-sanitized at render.');
expect_button(str_contains($html, 'currentColor'), 'Custom SVG follows icon color.');
$html = herizon_cars_button_render(['iconSource' => 'media', 'iconId' => 99]);
expect_button(!str_contains($html, 'hc-button__icon'), 'Removed media gives no broken image.');
unlink($GLOBALS['button_svg_file']);
echo "HCars Button: {$count} assertions PASS\n";
