<?php
/** Regenerate the public demo from real theme renderers inside WordPress. */
if (!defined('ABSPATH') || !function_exists('herizon_cars_button_render') || !class_exists('\Elementor\Plugin')) {
    throw new RuntimeException('Run with WordPress, Herizon Cars and Elementor loaded.');
}
$project = dirname(__DIR__);
$demo = $project . '/docs/portfolio/demo';
if (!is_dir($demo . '/media') && !mkdir($demo . '/media', 0755, true)) { throw new RuntimeException('Cannot create demo directory.'); }
$widget = static function (string $type, array $settings): string {
    $instance = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id' => 'hcdemo1', 'elType' => 'widget', 'widgetType' => $type, 'settings' => $settings,
    ]);
    if (!$instance) { throw new RuntimeException('Widget unavailable: ' . $type); }
    ob_start(); $instance->render_content(); return (string) ob_get_clean();
};
$button = static fn(string $variant, string $text, string $icon = 'fas:play'): string => herizon_cars_button_render([
    'variant' => $variant, 'text' => $text, 'url' => '#gallery', 'icon' => $icon,
]);
$switched = switch_to_locale('en_US');
try {
    $cards = '';
    foreach (['pill' => ['Pill', 'Discover our approach'], 'outline' => ['Outlined circle', 'Explore the collection'], 'solid' => ['Filled circle', 'View the process']] as $variant => [$label, $text]) {
        $cards .= '<article class="variant-card"><p class="eyebrow">' . esc_html($label) . '</p>' . $button($variant, $text) . '</article>';
    }
    $logo_items = []; $gallery_items = [];
    $colors = ['#81b3f5', '#c8d1db', '#73ada9'];
    $car = '<path d="M70 230h31l41-61c12-17 26-24 50-24h129c30 0 51 13 78 43l36 22 29 7c11 3 17 10 17 20v24H61v-14c0-9 3-14 9-17Z" fill="none" stroke="currentColor" stroke-width="3"/><path d="m151 192 30-33h121c18 0 35 10 56 33H151Z" fill="currentColor" opacity=".18"/><circle cx="150" cy="258" r="27" fill="#101010" stroke="currentColor" stroke-width="3"/><circle cx="150" cy="258" r="12" fill="currentColor" opacity=".4"/><circle cx="397" cy="258" r="27" fill="#101010" stroke="currentColor" stroke-width="3"/><circle cx="397" cy="258" r="12" fill="currentColor" opacity=".4"/>';
    for ($i = 1; $i <= 4; $i++) {
        $label = 'STUDIO ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 48"><path d="M8 24 22 10l14 14-14 14Z" fill="none" stroke="#81b3f5" stroke-width="2"/><text x="50" y="30" font-family="Arial,sans-serif" font-size="20" letter-spacing="2" fill="#d4d7dc">' . $label . '</text></svg>';
        file_put_contents($demo . '/media/mark-' . $i . '.svg', $svg);
        $logo_items[] = ['image' => ['url' => 'https://portfolio.invalid/media/mark-' . $i . '.svg'], 'alt' => 'Fictional mark ' . $i, 'link' => ['url' => '#gallery']];
    }
    foreach ($colors as $i => $color) {
        $number = $i + 1;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 540 380"><rect width="540" height="380" fill="#15191f"/><circle cx="420" cy="80" r="180" fill="' . $color . '" opacity=".06"/><path d="M0 300h540M270 0v380" stroke="#ffffff" opacity=".04"/><g color="' . $color . '">' . $car . '</g><text x="40" y="63" font-family="Arial,sans-serif" font-size="12" letter-spacing="3" fill="' . $color . '">STUDY 0' . $number . '</text></svg>';
        file_put_contents($demo . '/media/study-' . $number . '.svg', $svg);
        $gallery_items[] = ['image' => ['url' => 'https://portfolio.invalid/media/study-' . $number . '.svg'], 'alt' => 'Original vehicle illustration ' . $number, 'caption' => ['01 / Composed', '02 / Considered', '03 / Connected'][$i]];
    }
    $logos = $widget('herizon-logo-marquee', ['items' => $logo_items, 'pause_hover' => 'yes', 'fade' => 'yes']);
    $marquee = $widget('herizon-text-marquee', ['items' => [['text' => 'Thoughtful engineering'], ['text' => 'Editable by design']], 'separator' => '✦', 'pause_hover' => 'yes']);
    $gallery = $widget('herizon-elegant-gallery', ['items' => $gallery_items, 'limit' => 3, 'limit_tablet' => 3, 'limit_mobile' => 3]);
    $icons = [];
    foreach (['play', 'house', 'carrot'] as $icon) { $icons[$icon] = herizon_cars_button_icon(herizon_cars_button_normalize(['icon' => 'fas:' . $icon])); }
    $template = file_get_contents(__DIR__ . '/portfolio-demo.template.html');
    $asset = static function (string $entry) use ($project): string {
        $file = herizon_cars_manifest()[$entry]['file'] ?? '';
        if (!$file || !herizon_cars_asset_url($file)) { throw new RuntimeException('Build assets first: ' . $entry); }
        if (!is_file($project . '/dist/' . $file)) { throw new RuntimeException('Active theme assets do not match this checkout: ' . $entry); }
        return '../../../dist/' . $file;
    };
    $html = strtr($template, [
        '{{BUTTON_CSS}}' => $asset('src/scss/button.scss'), '{{WIDGET_CSS}}' => $asset('src/scss/widgets.scss'),
        '{{WIDGET_JS}}' => $asset('src/js/widgets.js'), '{{VARIANTS}}' => $cards,
        '{{LIVE_BUTTON}}' => $button('pill', 'Make it your own'), '{{LOGOS}}' => $logos,
        '{{MARQUEE}}' => $marquee, '{{GALLERY}}' => $gallery,
    ]);
    $html = str_replace('https://portfolio.invalid/media/', 'media/', $html);
    if (preg_match('/\{\{[A-Z_]+\}\}/', $html)) { throw new RuntimeException('Unresolved demo template token.'); }
    file_put_contents($demo . '/index.html', $html);
    file_put_contents($demo . '/icons.json', wp_json_encode($icons, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    return ['written' => true, 'variants' => 3, 'fictionalMarks' => 4, 'originalIllustrations' => 3, 'wordpressContentModified' => false];
} finally { if ($switched) { restore_previous_locale(); } }
