<?php
/** Native Elementor control surface for the shared HCars Button renderer. @package HerizonCars */
defined('ABSPATH') || exit;

class Herizon_Cars_Widget_Button extends Herizon_Cars_Widget_Base {
    private array $button_schema = [];
    private array $icon_catalog = [];

    public function get_name(): string { return 'herizon-button'; }
    public function get_title(): string { return __('HCars Button', 'herizon-cars'); }
    public function get_icon(): string { return 'eicon-button'; }
    public function get_keywords(): array { return ['herizon', 'button', 'link', 'icon', 'cta']; }
    public function get_style_depends(): array { return ['herizon-cars-button']; }
    public function get_script_depends(): array { return []; }

    protected function register_controls(): void {
        $this->button_schema = $this->read_json_asset('button-schema.json');
        $this->icon_catalog = $this->read_json_asset('button-icons.json');

        $this->start_controls_section('content', ['label' => __('Content', 'herizon-cars')]);
        $this->add_control('variant', [
            'label' => __('Variant', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SELECT,
            'options' => ['pill' => __('Pill', 'herizon-cars'), 'outline' => __('Outline', 'herizon-cars'), 'solid' => __('Solid', 'herizon-cars')],
            'default' => 'pill',
        ]);
        $this->add_control('text', ['label' => __('Text', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Button', 'herizon-cars'), 'label_block' => true]);
        $this->add_control('url', ['label' => __('URL', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true, 'description' => __('Accepts page links and anchors such as #contact.', 'herizon-cars')]);
        $this->add_control('newTab', ['label' => __('Open in new tab', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes']);
        $this->add_control('rel', ['label' => __('Link rel', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true]);
        $this->add_control('ariaLabel', ['label' => __('Accessible label', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true]);
        $this->add_control('iconEnabled', ['label' => __('Show icon', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes']);
        $this->add_control('iconSource', [
            'label' => __('Icon source', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SELECT,
            'options' => ['library' => __('Font Awesome library', 'herizon-cars'), 'media' => __('Media library (SVG)', 'herizon-cars')],
            'default' => 'library', 'condition' => ['iconEnabled' => 'yes'],
        ]);
        $this->add_control('icon', [
            'label' => __('Icon', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SELECT2,
            'options' => $this->icon_options(), 'default' => 'fas:play', 'label_block' => true,
            'multiple' => false, 'condition' => ['iconEnabled' => 'yes', 'iconSource' => 'library'],
        ]);
        $this->add_control('iconPreview', [
            'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => $this->icon_preview_markup(),
            'content_classes' => 'hc-button-icon-preview-control', 'condition' => ['iconEnabled' => 'yes', 'iconSource' => 'library'],
        ]);
        $this->add_control('iconMedia', [
            'label' => __('SVG icon', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::MEDIA,
            'media_types' => ['image'], 'condition' => ['iconEnabled' => 'yes', 'iconSource' => 'media'],
        ]);
        $this->add_control('iconId', ['type' => \Elementor\Controls_Manager::HIDDEN]);
        $this->add_control('iconPosition', [
            'label' => __('Icon position', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SELECT,
            'options' => ['left' => __('Left', 'herizon-cars'), 'right' => __('Right', 'herizon-cars')],
            'default' => 'left', 'condition' => ['iconEnabled' => 'yes'],
        ]);
        $this->add_control('iconContainer', ['label' => __('Use icon container', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'condition' => ['iconEnabled' => 'yes']]);
        $this->end_controls_section();

        $this->start_controls_section('design', ['label' => __('Design', 'herizon-cars')]);
        $this->add_control('duration', ['label' => __('Transition duration (ms)', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 0, 'max' => 2000, 'default' => 200]);
        $this->add_control('hoverMove', ['label' => __('Hover movement (px)', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => -30, 'max' => 30, 'default' => 3]);
        $this->add_responsive_control('layout', [
            'label' => __('Width', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SELECT,
            'options' => ['auto' => __('Auto', 'herizon-cars'), 'full' => __('Full width', 'herizon-cars'), 'custom' => __('Custom', 'herizon-cars')],
        ]);
        $this->add_responsive_control('customWidth', ['label' => __('Custom width', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SLIDER, 'size_units' => ['px', '%', 'vw', 'rem'], 'range' => ['px' => ['min' => 0, 'max' => 1600], '%' => ['min' => 0, 'max' => 100], 'vw' => ['min' => 0, 'max' => 100], 'rem' => ['min' => 0, 'max' => 100]], 'condition' => ['layout' => 'custom']]);
        $this->add_responsive_control('layoutAlign', [
            'label' => __('Alignment', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::CHOOSE,
            'options' => ['flex-start' => ['title' => __('Left', 'herizon-cars'), 'icon' => 'eicon-text-align-left'], 'center' => ['title' => __('Center', 'herizon-cars'), 'icon' => 'eicon-text-align-center'], 'flex-end' => ['title' => __('Right', 'herizon-cars'), 'icon' => 'eicon-text-align-right']],
        ]);
        $this->end_controls_section();

        $this->register_style_controls('normal', __('Normal', 'herizon-cars'));
        $this->register_style_controls('hover', __('Hover', 'herizon-cars'));
        $this->start_controls_section('reset', ['label' => __('Reset styles', 'herizon-cars')]);
        $this->add_control('resetStyles', ['type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => '<button type="button" class="button" data-hc-button-reset>' . esc_html__('Reset style overrides', 'herizon-cars') . '</button>', 'content_classes' => 'hc-button-reset-control']);
        $this->end_controls_section();
    }

    private function register_style_controls(string $state, string $label): void {
        $this->start_controls_section($state . '_style', ['label' => $label, 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        foreach ((array) ($this->button_schema['fields'] ?? []) as $field) {
            if (!is_array($field) || empty($field['key']) || empty($field['type'])) { continue; }
            $key = sanitize_key((string) $field['key']);
            $control = $state . '_' . $key;
            $args = ['label' => __((string) ($field['label'] ?? $key), 'herizon-cars'), 'label_block' => true];
            if ($field['type'] === 'background') {
                if (class_exists('\Elementor\Group_Control_Background')) {
                    $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
                        'name' => $control, 'label' => $args['label'], 'types' => ['classic', 'gradient'],
                        // Renderer owns the button CSS; never style Elementor's wrapper.
                        'include' => ['background', 'color', 'color_b', 'color_stop', 'color_b_stop', 'gradient_type', 'gradient_angle', 'gradient_position'],
                        'fields_options' => [
                            '__all' => ['selectors' => [], 'render_type' => 'template', 'responsive' => true],
                            'background' => ['default' => ''],
                            'color' => ['default' => ''],
                            'color_b' => ['default' => ''],
                            'gradient_type' => ['device_args' => ['tablet' => ['default' => ''], 'mobile' => ['default' => '']]],
                            'gradient_position' => ['device_args' => ['tablet' => ['default' => ''], 'mobile' => ['default' => '']]],
                            'gradient_angle' => ['size_units' => ['deg', 'turn', 'rad', 'grad']],
                            'color_stop' => ['size_units' => ['%']],
                            'color_b_stop' => ['size_units' => ['%']],
                        ],
                    ]);
                } else {
                    $this->add_responsive_control($control . '_color', $args + ['type' => \Elementor\Controls_Manager::COLOR]);
                }
                continue;
            }
            $type = $field['type'];
            if ($type === 'color') {
                $args['type'] = \Elementor\Controls_Manager::COLOR;
                $args['alpha'] = true;
            } elseif ($type === 'length') {
                $args['type'] = \Elementor\Controls_Manager::SLIDER;
                $args['size_units'] = ['px', '%', 'em', 'rem', 'vw', 'vh'];
                $min = $key === 'letterspacing' ? -200 : 0;
                $args['range'] = ['px' => ['min' => $min, 'max' => 1600], '%' => ['min' => $min, 'max' => 100], 'em' => ['min' => $min, 'max' => 100], 'rem' => ['min' => $min, 'max' => 100], 'vw' => ['min' => $min, 'max' => 100], 'vh' => ['min' => $min, 'max' => 100]];
            } elseif ($type === 'font') {
                $args['type'] = \Elementor\Controls_Manager::FONT;
            } elseif ($type === 'number') {
                $args['type'] = \Elementor\Controls_Manager::NUMBER;
                $args['step'] = 'any';
                $args['min'] = .5;
                $args['max'] = 5;
            } elseif ($type === 'select') {
                $args['type'] = \Elementor\Controls_Manager::SELECT;
                $args['options'] = ['' => __('Use preset / inherit', 'herizon-cars')] + array_map(static fn($option) => __($option, 'herizon-cars'), (array) ($field['options'] ?? []));
                $args['default'] = '';
            } elseif ($type === 'shadow') {
                if (class_exists('\Elementor\Group_Control_Box_Shadow')) {
                    $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => $control, 'label' => $args['label'], 'fields_options' => ['__all' => ['selectors' => [], 'render_type' => 'template']]]);
                }
                continue;
            } else { continue; }
            // Empty device values inherit through the shared responsive CSS aliases.
            $this->add_responsive_control($control, $args);
        }
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $attrs = [
            'variant' => $this->enum_value($settings['variant'] ?? '', ['pill', 'outline', 'solid'], 'pill'),
            'text' => (string) ($settings['text'] ?? ''),
            'url' => (string) ($settings['url'] ?? ''),
            'newTab' => !empty($settings['newTab']),
            'rel' => (string) ($settings['rel'] ?? ''),
            'ariaLabel' => (string) ($settings['ariaLabel'] ?? ''),
            'iconEnabled' => !empty($settings['iconEnabled']),
            'iconSource' => $this->enum_value($settings['iconSource'] ?? '', ['library', 'media'], 'library'),
            'icon' => (string) ($settings['icon'] ?? 'fas:play'),
            'iconId' => absint($settings['iconMedia']['id'] ?? $settings['iconId'] ?? 0),
            'iconPosition' => $this->enum_value($settings['iconPosition'] ?? '', ['left', 'right'], 'left'),
            'iconContainer' => !empty($settings['iconContainer']),
            'duration' => max(0, min(2000, (int) ($settings['duration'] ?? 200))),
            'hoverMove' => max(-30, min(30, (int) ($settings['hoverMove'] ?? 3))),
            'styles' => $this->style_values($settings),
        ];
        if (function_exists('herizon_cars_button_render')) {
            echo herizon_cars_button_render($attrs); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer owns context-specific escaping.
        }
    }

    private function style_values(array $settings): array {
        $styles = ['normal' => ['desktop' => [], 'tablet' => [], 'mobile' => []], 'hover' => ['desktop' => [], 'tablet' => [], 'mobile' => []]];
        foreach (['normal', 'hover'] as $state) {
            foreach ((array) (herizon_cars_button_schema()['fields'] ?? []) as $field) {
                if (!is_array($field) || empty($field['key'])) { continue; }
                $style_key = (string) $field['key'];
                $id = $state . '_' . sanitize_key($style_key);
                $type = $field['type'] ?? '';
                foreach (['desktop' => '', 'tablet' => '_tablet', 'mobile' => '_mobile'] as $device => $suffix) {
                    $value = null;
                    if ($type === 'background') { $value = $this->background_value($settings, $id, $device); }
                    elseif ($type === 'shadow') { $value = $device === 'desktop' ? $this->shadow_value($settings, $id) : ''; }
                    elseif ($type === 'length') { $value = $this->length_value($settings[$id . $suffix] ?? null); }
                    elseif ($type === 'number') { $raw = $settings[$id . $suffix] ?? null; $value = is_numeric($raw) ? (string) $raw : ''; }
                    elseif ($type === 'color') { $value = $this->setting_color($settings, $id . $suffix); }
                    else { $raw = $settings[$id . $suffix] ?? ''; $value = is_scalar($raw) ? trim((string) $raw) : ''; }
                    if (is_string($value) && $value !== '') { $styles[$state][$device][$style_key] = $value; }
                }
            }
        }
        foreach (['desktop' => '', 'tablet' => '_tablet', 'mobile' => '_mobile'] as $device => $suffix) {
            $layout = $settings['layout' . $suffix] ?? '';
            if (in_array($layout, ['auto', 'full', 'custom'], true)) {
                if ($layout === 'auto') { $styles['normal'][$device]['width'] = 'auto'; }
                elseif ($layout === 'full') { $styles['normal'][$device]['width'] = '100%'; }
                elseif (is_array($settings['customWidth' . $suffix] ?? null)) {
                    $width = $this->length_value($settings['customWidth' . $suffix]);
                    if ($width !== '') { $styles['normal'][$device]['width'] = $width; }
                }
            }
            $align = $settings['layoutAlign' . $suffix] ?? '';
            if (in_array($align, ['flex-start', 'center', 'flex-end'], true)) { $styles['normal'][$device]['align'] = $align; }
        }
        return $styles;
    }

    /** Resolve a device value from explicit mobile/tablet/desktop settings. */
    private function device_value(array $settings, string $id, string $device, $default = '') {
        $suffixes = $device === 'mobile' ? ['_mobile', '_tablet', ''] : ($device === 'tablet' ? ['_tablet', ''] : ['']);
        foreach ($suffixes as $suffix) {
            $value = $settings[$id . $suffix] ?? null;
            if (is_array($value) && (!isset($value['size']) || $value['size'] === '' || $value['size'] === null)) { continue; }
            if ($value !== null && $value !== '') { return $value; }
        }
        return $default;
    }

    /** Elementor's display settings do not resolve __globals__ color references. */
    private function setting_color(array $settings, string $id): string {
        $reference = $settings['__globals__'][$id] ?? '';
        if (is_string($reference) && preg_match('~\Aglobals/colors\?id=([a-zA-Z0-9_-]+)\z~D', $reference, $match)) {
            return 'var(--e-global-color-' . $match[1] . ')';
        }
        return $this->safe_css_color($settings[$id] ?? '');
    }

    private function device_color(array $settings, string $id, string $device): string {
        $suffixes = $device === 'mobile' ? ['_mobile', '_tablet', ''] : ($device === 'tablet' ? ['_tablet', ''] : ['']);
        foreach ($suffixes as $suffix) {
            $value = $this->setting_color($settings, $id . $suffix);
            if ($value !== '') { return $value; }
        }
        return '';
    }

    private function gradient_degrees($value): float {
        if (!is_array($value)) { return is_numeric($value) ? (float) $value : 180.0; }
        $size = $value['size'] ?? null;
        if (!is_numeric($size) || !is_finite((float) $size)) { return 180.0; }
        $number = (float) $size;
        $degrees = match ($value['unit'] ?? 'deg') {
            'turn' => $number * 360,
            'rad' => $number * 180 / M_PI,
            'grad' => $number * .9,
            'deg' => $number,
            default => 180.0,
        };
        return is_finite($degrees) ? $degrees : 180.0;
    }

    private function background_value(array $settings, string $id, string $device = 'desktop'): string {
        $type = $this->device_value($settings, $id . '_background', $device);
        $first = $this->device_color($settings, $id . '_color', $device);
        if ($type === 'gradient') {
            $second = $this->device_color($settings, $id . '_color_b', $device);
            if ($first === '' || $second === '') { return ''; }
            $stop_a = max(0, min(100, $this->slider_size($this->device_value($settings, $id . '_color_stop', $device, 0))));
            $stop_b = max(0, min(100, $this->slider_size($this->device_value($settings, $id . '_color_b_stop', $device, 100))));
            $stops = $first . ' ' . $stop_a . '%, ' . $second . ' ' . $stop_b . '%';
            if ($this->device_value($settings, $id . '_gradient_type', $device, 'linear') === 'radial') {
                $position = $this->device_value($settings, $id . '_gradient_position', $device, 'center center');
                $positions = ['center center', 'center left', 'center right', 'top center', 'top left', 'top right', 'bottom center', 'bottom left', 'bottom right'];
                if (!in_array($position, $positions, true)) { $position = 'center center'; }
                return 'radial-gradient(at ' . $position . ', ' . $stops . ')';
            }
            $angle = $this->gradient_degrees($this->device_value($settings, $id . '_gradient_angle', $device, ['size' => 180, 'unit' => 'deg']));
            // Bound huge values, preserving equivalent negative and multi-turn angles.
            $angle = round(fmod($angle, 360.0), 4);
            return 'linear-gradient(' . $angle . 'deg, ' . $stops . ')';
        }
        return $first;
    }

    private function shadow_value(array $settings, string $id): string {
        if (($settings[$id . '_box_shadow_type'] ?? '') !== 'yes') { return ''; }
        $shadow = (array) ($settings[$id . '_box_shadow'] ?? []);
        if (!$shadow || empty($shadow['horizontal']) && empty($shadow['vertical']) && empty($shadow['blur']) && empty($shadow['spread'])) { return ''; }
        $unit = static fn($value, $fallback = 'px') => is_numeric($value) ? (string) $value . $fallback : '';
        $color = $this->safe_css_color($shadow['color'] ?? '');
        if ($color === '') { $color = 'rgba(0,0,0,.2)'; }
        return (($settings[$id . '_box_shadow_position'] ?? '') === 'inset' ? 'inset ' : '') . $unit($shadow['horizontal'] ?? 0) . ' ' . $unit($shadow['vertical'] ?? 0) . ' ' . $unit($shadow['blur'] ?? 0) . ' ' . $unit($shadow['spread'] ?? 0) . ' ' . $color;
    }

    private function length_value($raw): string {
        if (!is_array($raw) || !is_numeric($raw['size'] ?? null)) { return ''; }
        $size = (float) $raw['size'];
        if (!is_finite($size) || abs($size) > 10000) { return ''; }
        $unit = in_array(($raw['unit'] ?? 'px'), ['px', '%', 'em', 'rem', 'vw', 'vh'], true) ? $raw['unit'] : 'px';
        $formatted = rtrim(rtrim(number_format($size, 4, '.', ''), '0'), '.');
        return ($formatted !== '' ? $formatted : '0') . $unit;
    }

    private function slider_size($value): float {
        if (is_array($value)) { $value = $value['size'] ?? 0; }
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function safe_css_color($raw): string {
        if (!is_string($raw)) { return ''; }
        $raw = trim($raw);
        if (sanitize_hex_color($raw) || preg_match('/\A#(?:[0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})\z/i', $raw)) { return $raw; }
        if (preg_match('/\\Avar\\(--e-global-color-[a-zA-Z0-9_-]+\\)\\z/', $raw)) { return $raw; }
        if ($raw === 'transparent' || preg_match('/\A(?:rgba?|hsla?)\([0-9.,%\s+-]+\)\z/i', $raw)) { return $raw; }
        return '';
    }

    private function enum_value($value, array $allowed, string $fallback): string {
        return is_string($value) && in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function read_json_asset(string $name): array {
        $path = dirname(__DIR__, 2) . '/assets/' . $name;
        if (!is_readable($path)) { return []; }
        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data)) { return []; }
        return isset($data['fields']) && is_array($data['fields']) ? $data : $data;
    }

    private function icon_options(): array {
        $icons = $this->icon_catalog['icons'] ?? $this->icon_catalog;
        $options = [];
        foreach ((array) $icons as $id => $icon) {
            if (!is_array($icon)) { continue; }
            $label = sanitize_text_field((string) ($icon['label'] ?? $id));
            $options[(string) $id] = $label . ' (' . (string) $id . ')';
        }
        if (!isset($options['fas:play'])) { $options['fas:play'] = __('Play', 'herizon-cars') . ' (fas:play)'; }
        return $options;
    }

    private function icon_preview_markup(): string {
        $catalog_url = defined('HERIZON_CARS_URL') ? HERIZON_CARS_URL . 'assets/button-icons.json' : '';
        return '<div class="hc-button-icon-preview" data-hc-button-icon-preview data-icons-url="' . esc_url($catalog_url) . '"><span class="hc-button-icon-preview__icon"><span aria-hidden="true">▶</span></span><span>' . esc_html__('Local icon preview', 'herizon-cars') . '</span></div>';
    }
}
