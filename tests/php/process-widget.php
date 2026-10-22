<?php
/** Process widget rendering and safety checks without a running WordPress site. */
namespace Elementor {
    class Widget_Base {
        public array $test_settings = [];
        public array $controls = [];
        public array $attrs = [];
        public array $groups = [];
        public function get_settings_for_display() { return $this->test_settings; }
        public function render_for_test(): string { ob_start(); $this->render(); return (string) ob_get_clean(); }
        public function register_for_test(): void { $this->register_controls(); }
        public function add_inline_editing_attributes($id, $toolbar = 'basic'): void {}
        public function add_render_attribute($id,$key,$value): void { $this->attrs[$id][$key] = $value; }
        public function get_render_attribute_string($id): string { return implode(' ',array_map(static fn($key,$value) => $key . '="' . htmlspecialchars($value,ENT_QUOTES) . '"', array_keys($this->attrs[$id] ?? []), array_values($this->attrs[$id] ?? []))); }
        protected function render(): void {}
        protected function register_controls(): void {}
        public function start_controls_section($id, $args = []): void {}
        public function end_controls_section(): void {}
        public function add_control($id, $args = []): void { $this->controls[$id] = $args; }
        public function add_responsive_control($id, $args = []): void { $this->controls[$id] = $args; }
        public function add_group_control($type, $args = []): void { $this->groups[$args['name']] = $args; }
    }
    class Controls_Manager {
        public const TAB_STYLE = 'style'; public const TEXT = 'text'; public const TEXTAREA = 'textarea';
        public const SELECT = 'select'; public const SELECT2 = 'select2'; public const MEDIA = 'media';
        public const SWITCHER = 'switcher'; public const REPEATER = 'repeater'; public const SLIDER = 'slider';
        public const HEADING = 'heading'; public const COLOR = 'color'; public const NUMBER = 'number'; public const FONT = 'font';
    }
    class Repeater { public array $controls=[]; public function add_control($id, $args = []): void { $this->controls[$id] = $args; } public function get_controls(): array { return $this->controls; } }
    class Group_Control_Background { public static function get_type(): string { return 'background'; } }
    class Group_Control_Border { public static function get_type(): string { return 'border'; } }
    class Group_Control_Box_Shadow { public static function get_type(): string { return 'shadow'; } }
    class Group_Control_Typography { public static function get_type(): string { return 'typography'; } }
}
namespace {
    define('ABSPATH', __DIR__ . '/');
    define('HERIZON_CARS_PATH', dirname(__DIR__, 2) . '/');
    function __($text, $domain = '') { return $text; }
    function esc_html__($text, $domain = '') { return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    function esc_html($text) { return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    function esc_attr($text) { return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    function esc_url_raw($url, $protocols = []) { $scheme = parse_url($url, PHP_URL_SCHEME); return !$scheme || in_array(strtolower($scheme), $protocols, true) ? $url : ''; }
    function esc_url($url, $protocols = []) { return esc_attr(esc_url_raw($url, $protocols)); }
    function sanitize_html_class($text) { return preg_replace('/[^a-zA-Z0-9_-]/', '', $text); }
    function sanitize_key($text) { return strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $text)); }
    function sanitize_text_field($text) { return trim(strip_tags((string) $text)); }
    function sanitize_hex_color($text) { return preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', (string) $text) ? $text : ''; }
    function absint($value) { return abs((int) $value); }
    function wp_enqueue_style($handle) {}
    function get_post_type($id) { return false; }
    function wp_attachment_is_image($id) { return false; }
    function get_post_meta($id, $key, $single = false) { return ''; }
    function wp_get_attachment_image($id, $size, $icon = false, $attrs = []) { return ''; }
    function sanitize_title($text) { return sanitize_html_class($text); }
    require HERIZON_CARS_PATH . 'inc/components/button.php';
    require HERIZON_CARS_PATH . 'inc/widgets/class-widget-base.php';
    require HERIZON_CARS_PATH . 'inc/widgets/class-widget-process.php';

    $assertions = 0;
    function process_expect(bool $condition, string $message): void {
        global $assertions; $assertions++;
        if (!$condition) { throw new \RuntimeException($message); }
    }
    $widget = new \Herizon_Cars_Widget_Process();
    process_expect($widget->get_name() === 'herizon-process', 'Stable Elementor widget name.');
    process_expect($widget->get_title() === 'HCars Process', 'Expected Elementor widget title.');
    process_expect($widget->get_categories() === ['herizon-cars'], 'Widget is in the Herizon Cars category.');
    process_expect($widget->get_style_depends() === ['herizon-cars-process', 'herizon-cars-button'], 'Only the process and shared button styles are required.');
    process_expect($widget->get_script_depends() === [], 'Server-rendered process requires no frontend script.');

    $widget->register_for_test();
    process_expect(isset($widget->controls['steps']), 'Native controls register without missing helpers.');
    process_expect(($widget->controls['columns']['mobile_default'] ?? null) === 1, 'Mobile defaults to one column.');
    process_expect(($widget->controls['cut']['mobile_default']['size'] ?? null) === 0, 'Mobile diagonal control has a valid zero-sized slider default.');
    process_expect(($widget->groups['button_hover_iconbackground']['fields_options']['__all']['selectors'] ?? null) === [], 'Button renderer owns icon hover CSS to preserve parent focus.');
    $widget->test_settings = [
        'eyebrow' => 'Így dolgozom', 'title' => "Lépésről lépésre\na megfelelő autóig.", 'title_tag' => 'h2',
        'description' => 'Személyes segítség & tapasztalat.', 'button_text' => 'Nézd meg', 'button_url' => '#folyamat',
        'button_variant' => 'solid', 'button_icon_enabled' => 'yes', 'steps' => [
            ['number' => '01', 'title' => 'Kiválasztás', 'description' => 'Csak gondosan ellenőrzött autókat ajánlok.'],
            ['number' => '02', 'title' => 'Egyedi keresés', 'description' => 'Németországból, a te igényeidre szabva.'],
            ['number' => '03', 'title' => 'Átadás', 'description' => 'Végigkísérlek a teljes folyamaton.'],
        ],
    ];
    $html = $widget->render_for_test();
    process_expect(str_contains($html, '<section class="hc-process">') && str_contains($html, 'class="hc-process__background" aria-hidden="true"><') && str_contains($html, 'class="hc-process__overlay" aria-hidden="true"'), 'Decorative background and overlay layers render.');
    process_expect(str_contains($html, 'class="hc-process__eyebrow"') && str_contains($html, 'Így dolgozom</p>') && str_contains($html, '<h2 class="hc-process__title"'), 'Escaped copy and validated heading render.');
    process_expect(str_contains($html, 'Lépésről lépésre<br />') && str_contains($html, 'Személyes segítség &amp; tapasztalat.'), 'Multiline copy preserves line breaks and escapes text.');
    process_expect(str_contains($html, 'class="hc-process__steps" role="list"') && substr_count($html, 'class="hc-process__step elementor-repeater-item-') === 3, 'Three text-only default steps render without images.');
    process_expect(str_contains($html, '>01</span>') && str_contains($html, 'Kiválasztás') && str_contains($html, 'Végigkísérlek a teljes folyamaton.'), 'Default step numbers and localized content render.');
    process_expect(str_contains($html, 'class="hc-process__cta"') && str_contains($html, 'href="#folyamat"'), 'Shared button renderer supplies a normal anchor.');
    process_expect(!str_contains($html, '<img'), 'Missing images preserve copy and do not emit broken image markup.');

    $widget->test_settings['title_tag'] = 'script';
    $widget->test_settings['steps'] = [[
        '_id' => 'step-a1', 'number' => '', 'title' => 'Custom <step>', 'description' => 'Readable details',
        'url' => 'javascript:alert(1)', 'new_tab' => 'yes', 'image' => [],
    ], [
        '_id' => 'step-a2', 'number' => '09', 'title' => 'Linked step', 'description' => 'Link text',
        'url' => 'https://example.test/step', 'new_tab' => 'yes', 'image' => [],
    ]];
    $html = $widget->render_for_test();
    process_expect(str_contains($html, '<h2 class="hc-process__title"'), 'Invalid heading tags fall back to h2.');
    process_expect(str_contains($html, 'elementor-repeater-item-step-a1') && str_contains($html, '>01</span>'), 'Missing custom number falls back to its order.');
    process_expect(!str_contains($html, '<step>') && str_contains($html, 'Custom &lt;step&gt;'), 'Step text cannot inject markup.');
    process_expect(!str_contains($html, 'javascript:') && !str_contains($html, 'href="javascript'), 'Unsafe card URLs do not create links.');
    process_expect(str_contains($html, 'href="https://example.test/step" target="_blank" rel="noopener noreferrer"'), 'Safe new-tab links receive protected rel values.');
    process_expect(str_contains($html, 'class="hc-process__card-link"') && str_contains($html, 'class="hc-process__card-content"'), 'Optional link wraps card content in the expected structure.');
    $widget->test_settings['steps'] = [];
    $html = $widget->render_for_test();
    process_expect(str_contains($html, 'class="hc-process__steps" role="list"') && !str_contains($html, 'hc-process__step '), 'Removing all repeater steps leaves an empty list.');
    $widget->test_settings['steps'] = [['_id'=>'blank','url'=>'https://example.test/empty','number'=>'99']];
    $widget->test_settings['button_url']='javascript:alert(1)';
    $html=$widget->render_for_test();
    process_expect(!str_contains($html, 'hc-process__card-link'), 'URL-only cards do not create unnamed links.');
    process_expect(!str_contains($html, 'javascript:'), 'Shared CTA renderer rejects unsafe destinations.');
    $widget->test_settings['button_url']='#folyamat';
    $widget->test_settings['button_normal_background_background']='gradient';
    $widget->test_settings['button_normal_background_color']='#123456';
    $widget->test_settings['button_normal_background_color_b']='#abcdef';
    $widget->test_settings['button_normal_background_gradient_angle']=['size'=>90,'unit'=>'deg'];
    $html=$widget->render_for_test();
    process_expect(str_contains($html,'linear-gradient(90deg,'), 'Shared conversion renders selected background gradient.');
    echo "HCars Process widget: {$assertions} assertions PASS\n";
}
