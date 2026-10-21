<?php
/** Vite manifest-backed assets; no broken URL fallback. @package HerizonCars */
if (!defined('ABSPATH')) { exit; }

function herizon_cars_manifest(): array {
    static $manifest;
    if (isset($manifest)) { return $manifest; }
    $path = HERIZON_CARS_PATH . 'dist/.vite/manifest.json';
    $data = is_readable($path) ? json_decode((string) file_get_contents($path), true) : null;
    $manifest = is_array($data) ? $data : [];
    return $manifest;
}

function herizon_cars_asset_url(string $file): string {
    if (!preg_match('~\Aassets/[a-zA-Z0-9_./-]+\z~D', $file) || str_contains($file, '..')) { return ''; }
    return is_file(HERIZON_CARS_PATH . 'dist/' . $file) ? HERIZON_CARS_URL . 'dist/' . $file : '';
}

function herizon_cars_entry_css(string $entry, array $seen = []): array {
    $manifest = herizon_cars_manifest();
    if (isset($seen[$entry]) || !isset($manifest[$entry])) { return []; }
    $seen[$entry] = true;
    $node = $manifest[$entry];
    $css = (array) ($node['css'] ?? []);
    if (str_ends_with((string) ($node['file'] ?? ''), '.css')) { $css[] = $node['file']; }
    foreach ((array) ($node['imports'] ?? []) as $import) { $css = array_merge($css, herizon_cars_entry_css($import, $seen)); }
    return array_values(array_unique(array_filter($css, 'is_string')));
}

function herizon_cars_register_assets(): void {
    $manifest = herizon_cars_manifest();
    foreach (['herizon-cars-main' => 'src/js/main.js', 'herizon-cars-widgets' => 'src/js/widgets.js', 'herizon-cars-button-editor' => 'src/js/button-editor.jsx', 'herizon-cars-button-elementor' => 'src/js/button-elementor.js'] as $handle => $entry) {
        $url = herizon_cars_asset_url((string) ($manifest[$entry]['file'] ?? ''));
        $deps = $handle === 'herizon-cars-button-editor' ? ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-core-data'] : ($handle === 'herizon-cars-button-elementor' ? ['jquery', 'elementor-editor'] : []);
        if ($url) {
            wp_register_script($handle, $url, $deps, null, ['in_footer' => true]);
            if (in_array($handle, ['herizon-cars-button-editor', 'herizon-cars-button-elementor'], true)) { wp_set_script_translations($handle, 'herizon-cars', HERIZON_CARS_PATH . 'languages'); }
        }
    }
    foreach (['herizon-cars-main' => 'src/js/main.js', 'herizon-cars-widgets' => 'src/scss/widgets.scss', 'herizon-cars-button' => 'src/scss/button.scss'] as $handle => $entry) {
        $dependencies = [];
        foreach (herizon_cars_entry_css($entry) as $index => $css) {
            $url = herizon_cars_asset_url($css);
            if (!$url) { continue; }
            $css_handle = $handle . '-part-' . $index;
            wp_register_style($css_handle, $url, [], null);
            $dependencies[] = $css_handle;
        }
        if ($dependencies) { wp_register_style($handle, false, $dependencies, HERIZON_CARS_VERSION); }
    }
}
add_action('init', 'herizon_cars_register_assets', 20);

/** Resolve background from the active kit, including native global color references. */
function herizon_cars_kit_background(): string {
    $kit_id = (int) get_option('elementor_active_kit');
    $settings = $kit_id ? get_post_meta($kit_id, '_elementor_page_settings', true) : [];
    if (!is_array($settings)) { return '#101010'; }
    $global = $settings['__globals__']['body_background_color'] ?? '';
    if (is_string($global) && preg_match('/(?:\?|&)id=([a-zA-Z0-9_-]+)(?:&|$)/', $global, $match)) {
        return 'var(--e-global-color-' . $match[1] . ', #101010)';
    }
    $color = $settings['body_background_color'] ?? '';
    return is_string($color) && sanitize_hex_color($color) ? sanitize_hex_color($color) : '#101010';
}

add_filter('script_loader_tag', static function (string $tag, string $handle): string {
    if (!in_array($handle, ['herizon-cars-main', 'herizon-cars-widgets', 'herizon-cars-button-editor', 'herizon-cars-button-elementor'], true)) { return $tag; }
    $processor = new WP_HTML_Tag_Processor($tag);
    // WordPress includes inline configuration tags before the external script.
    // Mark only the external entry; leave configuration synchronous and intact.
    while ($processor->next_tag('SCRIPT')) {
        if ($processor->get_attribute('src')) { $processor->set_attribute('type', 'module'); }
    }
    return $processor->get_updated_html();
}, 10, 2);

add_action('wp_enqueue_scripts', static function (): void {
    herizon_cars_register_assets();
    // CSS utilities remain available inside the preview; disruptive JS does not.
    wp_enqueue_style('herizon-cars-main');
    wp_add_inline_style('herizon-cars-main', 'body{--hc-bg:' . herizon_cars_kit_background() . ';}');
    if (!herizon_cars_is_public_view() || !wp_script_is('herizon-cars-main', 'registered')) { return; }
    $settings = herizon_cars_settings();
    $config = [
        'modules' => $settings['modules'],
        'selectors' => $settings['selectors'],
        'anchorOffset' => (int) ($settings['anchor_offset'] ?? 0),
    ];
    wp_add_inline_script('herizon-cars-main', 'window.herizonCarsConfig = ' . wp_json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';', 'before');
    wp_enqueue_script('herizon-cars-main');
}, 30);

add_action('admin_notices', static function (): void {
    if (!current_user_can('manage_options')) { return; }
    $manifest = herizon_cars_manifest();
    foreach (['src/js/main.js', 'src/js/widgets.js', 'src/scss/widgets.scss', 'src/js/button-editor.jsx', 'src/js/button-elementor.js', 'src/scss/button.scss'] as $entry) {
        if (!herizon_cars_asset_url((string) ($manifest[$entry]['file'] ?? ''))) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Herizon Cars: compiled assets are missing. Run pnpm build before delivery.', 'herizon-cars') . '</p></div>';
            break;
        }
    }
});
