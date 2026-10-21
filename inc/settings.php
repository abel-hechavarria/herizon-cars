<?php
/** Appearance settings and runtime context. @package HerizonCars */
defined('ABSPATH') || exit;

function herizon_cars_module_labels(): array {
    return [
        'preloader' => __('Preloader', 'herizon-cars'),
        'blur' => __('Bottom blur', 'herizon-cars'),
        'cursor' => __('Decorative cursor', 'herizon-cars'),
        'smooth_scroll' => __('Smooth scrolling', 'herizon-cars'),
        'back_to_top' => __('Back to top', 'herizon-cars'),
        'sticky_cta' => __('Floating contact panel', 'herizon-cars'),
        'uploads' => __('Administrator SVG / SVGZ / JSON uploads', 'herizon-cars'),
        'https_normalization' => __('Same-host HTTPS normalization', 'herizon-cars'),
        'jquery_ui' => __('Remove unused jQuery UI on front page', 'herizon-cars'),
        'image_sizes' => __('Optimized generated image sizes', 'herizon-cars'),
        'shortlinks' => __('Remove shortlinks', 'herizon-cars'),
        'self_pingbacks' => __('Prevent self pingbacks', 'herizon-cars'),
        'body_class' => __('Content and slug body classes', 'herizon-cars'),
        'site_icon' => __('Disable native site icon output', 'herizon-cars'),
        'year' => __('Current year shortcodes', 'herizon-cars'),
    ];
}
function herizon_cars_settings_defaults(): array {
    // Public labels follow the site language, even if an administrator uses a
    // different dashboard language when first saving the default settings.
    $switched = function_exists('switch_to_locale') && switch_to_locale(get_option('WPLANG') ?: 'en_US');
    $defaults = [
        'modules' => array_fill_keys(array_keys(herizon_cars_module_labels()), true),
        'cta' => [
            'phone' => '', 'whatsapp' => '', 'email' => '',
            'label' => __('Contact us', 'herizon-cars'),
            'phone_label' => __('Call', 'herizon-cars'),
            'whatsapp_label' => __('WhatsApp', 'herizon-cars'),
            'email_label' => __('Email', 'herizon-cars'),
            'text' => __('How can we help you?', 'herizon-cars'),
        ],
        'selectors' => [
            'hero' => '.hc-hero',
            'footer' => '.hc-footer, footer.site-footer, .elementor-location-footer',
            'header' => '.hc-sticky-header',
        ],
        'anchor_offset' => 0,
        'preloader_text' => __('Loading', 'herizon-cars'),
    ];
    if ($switched) { restore_previous_locale(); }
    return $defaults;
}
function herizon_cars_settings(): array {
    $defaults = herizon_cars_settings_defaults();
    $stored = get_option('herizon_cars_settings', []);
    if (!is_array($stored)) {
        return $defaults;
    }
    foreach (['modules', 'cta', 'selectors'] as $group) {
        $stored[$group] = array_replace($defaults[$group], is_array($stored[$group] ?? null) ? $stored[$group] : []);
    }
    return array_replace($defaults, $stored);
}
function herizon_cars_module_enabled(string $module): bool {
    return !empty(herizon_cars_settings()['modules'][$module]);
}
function herizon_cars_is_public_view(): bool {
    if (is_admin() || wp_doing_ajax() || wp_is_json_request() || is_feed() || is_embed()
        || (defined('REST_REQUEST') && REST_REQUEST) || (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST)
        || (defined('DOING_CRON') && DOING_CRON) || isset($_GET['elementor-preview'])) {
        return false;
    }
    if (class_exists('\Elementor\Plugin')) {
        $elementor = \Elementor\Plugin::$instance;
        if (($elementor->editor && $elementor->editor->is_edit_mode())
            || ($elementor->preview && $elementor->preview->is_preview_mode())) {
            return false;
        }
    }
    return true;
}
function herizon_cars_sanitize_settings($input): array {
    $input = is_array($input) ? $input : [];
    $defaults = herizon_cars_settings_defaults();
    $result = $defaults;
    $modules = is_array($input['modules'] ?? null) ? $input['modules'] : [];
    foreach ($defaults['modules'] as $key => $default) {
        $result['modules'][$key] = isset($modules[$key]) && in_array($modules[$key], [true, 1, '1', 'on'], true);
    }
    $cta = is_array($input['cta'] ?? null) ? $input['cta'] : [];
    $text = static fn($value): string => is_scalar($value) ? sanitize_text_field((string) $value) : '';
    foreach ($defaults['cta'] as $key => $default) {
        $result['cta'][$key] = isset($cta[$key]) ? $text($cta[$key]) : $default;
    }
    $phone = $result['cta']['phone'];
    $result['cta']['phone'] = preg_match('/^\+?[0-9 ()-]{3,40}$/D', $phone) ? $phone : '';
    $whatsapp = preg_replace('/[\s()+-]/', '', $result['cta']['whatsapp']);
    $result['cta']['whatsapp'] = preg_match('/^[0-9]{6,15}$/D', $whatsapp) ? $whatsapp : '';
    $result['cta']['email'] = sanitize_email($result['cta']['email']);
    $selectors = is_array($input['selectors'] ?? null) ? $input['selectors'] : [];
    foreach ($defaults['selectors'] as $key => $default) {
        $value = isset($selectors[$key]) ? trim($text($selectors[$key])) : $default;
        $result['selectors'][$key] = strlen($value) <= 500 && !preg_match('/[<>\x00-\x1f{};]/', $value) ? $value : $default;
    }
    $offset = $input['anchor_offset'] ?? 0;
    $result['anchor_offset'] = is_scalar($offset) ? max(-1000, min(1000, (int) $offset)) : 0;
    $result['preloader_text'] = isset($input['preloader_text']) ? $text($input['preloader_text']) : $defaults['preloader_text'];
    return $result;
}
add_action('admin_init', static function (): void {
    register_setting('herizon_cars', 'herizon_cars_settings', [
        'type' => 'array', 'sanitize_callback' => 'herizon_cars_sanitize_settings',
        'default' => herizon_cars_settings_defaults(), 'show_in_rest' => false,
    ]);
});
add_action('admin_menu', static function (): void {
    add_theme_page('Herizon Cars', 'Herizon Cars', 'manage_options', 'herizon-cars', 'herizon_cars_render_settings');
});
function herizon_cars_render_settings(): void {
    if (!current_user_can('manage_options')) {
        return;
    }
    $settings = herizon_cars_settings();
    $fields = [
        'cta' => [
            'phone' => __('Phone number', 'herizon-cars'),
            'whatsapp' => __('WhatsApp number (international format)', 'herizon-cars'),
            'email' => __('Email address', 'herizon-cars'),
            'label' => __('Contact button label', 'herizon-cars'),
            'phone_label' => __('Call label', 'herizon-cars'),
            'whatsapp_label' => __('WhatsApp label', 'herizon-cars'),
            'email_label' => __('Email label', 'herizon-cars'),
            'text' => __('Contact panel text', 'herizon-cars'),
        ],
        'selectors' => [
            'hero' => __('Hero selector', 'herizon-cars'),
            'footer' => __('Footer selector', 'herizon-cars'),
            'header' => __('Sticky header selector', 'herizon-cars'),
        ],
    ];
    ?>
    <div class="wrap"><h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php settings_errors('herizon_cars_settings'); ?>
        <form action="options.php" method="post">
            <?php settings_fields('herizon_cars'); ?>
            <h2><?php esc_html_e('Modules', 'herizon-cars'); ?></h2>
            <fieldset><legend class="screen-reader-text"><?php esc_html_e('Enable modules', 'herizon-cars'); ?></legend>
                <?php foreach (herizon_cars_module_labels() as $key => $label) : ?>
                    <p><label><input type="checkbox" name="herizon_cars_settings[modules][<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($settings['modules'][$key])); ?>> <?php echo esc_html($label); ?></label></p>
                <?php endforeach; ?>
            </fieldset>
            <h2><?php esc_html_e('Contact and page references', 'herizon-cars'); ?></h2>
            <p><?php esc_html_e('The contact panel stays hidden until at least one valid contact channel is saved.', 'herizon-cars'); ?></p>
            <table class="form-table" role="presentation"><tbody>
                <?php foreach ($fields as $group => $group_fields) : foreach ($group_fields as $key => $label) : $id = 'hc-' . $group . '-' . $key; ?>
                    <tr><th scope="row"><label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label></th>
                        <td><input class="regular-text" id="<?php echo esc_attr($id); ?>" name="herizon_cars_settings[<?php echo esc_attr($group); ?>][<?php echo esc_attr($key); ?>]" type="<?php echo $key === 'email' ? 'email' : 'text'; ?>" value="<?php echo esc_attr($settings[$group][$key]); ?>"></td></tr>
                <?php endforeach; endforeach; ?>
                <tr><th scope="row"><label for="hc-preloader-text"><?php esc_html_e('Preloader text', 'herizon-cars'); ?></label></th><td><input class="regular-text" id="hc-preloader-text" name="herizon_cars_settings[preloader_text]" value="<?php echo esc_attr($settings['preloader_text']); ?>"></td></tr>
                <tr><th scope="row"><label for="hc-anchor-offset"><?php esc_html_e('Anchor offset (pixels)', 'herizon-cars'); ?></label></th><td><input id="hc-anchor-offset" name="herizon_cars_settings[anchor_offset]" type="number" min="-1000" max="1000" value="<?php echo esc_attr((string) $settings['anchor_offset']); ?>"></td></tr>
            </tbody></table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
