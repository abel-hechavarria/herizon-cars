<?php
/** Editable, server-rendered process section for Elementor. @package HerizonCars */
defined('ABSPATH') || exit;

class Herizon_Cars_Widget_Process extends Herizon_Cars_Widget_Base {
    public function get_name(): string { return 'herizon-process'; }
    public function get_title(): string { return __('HCars Process', 'herizon-cars'); }
    public function get_icon(): string { return 'eicon-flow'; }
    public function get_keywords(): array { return ['herizon', 'process', 'steps', 'how it works']; }
    public function get_style_depends(): array { return ['herizon-cars-process', 'herizon-cars-button']; }
    public function get_script_depends(): array { return []; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Content', 'herizon-cars')]);
        $this->add_control('eyebrow', ['label' => __('Eyebrow', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Így dolgozom', 'herizon-cars'), 'label_block' => true, 'dynamic' => ['active' => true]]);
        $this->add_control('title', ['label' => __('Title', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __("Lépésről lépésre\na megfelelő autóig.", 'herizon-cars'), 'rows' => 3, 'label_block' => true, 'dynamic' => ['active' => true]]);
        $this->add_control('title_tag', ['label' => __('Title HTML tag', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SELECT, 'options' => ['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6', 'div' => 'div'], 'default' => 'h2']);
        $this->add_control('description', ['label' => __('Description', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __('Személyes segítség, tapasztalat és megbízható források – hogy te nyugodtan élvezhesd a következő autódat.', 'herizon-cars'), 'rows' => 4, 'label_block' => true, 'dynamic' => ['active' => true]]);
        $this->add_control('background_image', ['label' => __('Section background image', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::MEDIA, 'media_types' => ['image']]);
        $this->add_control('background_image_position', ['label' => __('Background image position', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SELECT, 'options' => ['center center' => __('Center', 'herizon-cars'), 'center top' => __('Top', 'herizon-cars'), 'center bottom' => __('Bottom', 'herizon-cars'), 'left center' => __('Left', 'herizon-cars'), 'right center' => __('Right', 'herizon-cars')], 'default' => 'center center', 'selectors' => ['{{WRAPPER}} .hc-process__background-image' => 'object-position: {{VALUE}};']]);

        $this->add_control('button_enabled', ['label' => __('Show button', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes']);
        $this->add_control('button_text', ['label' => __('Button text', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Nézd meg a folyamatot.', 'herizon-cars'), 'label_block' => true, 'condition' => ['button_enabled' => 'yes']]);
        $this->add_control('button_url', ['label' => __('Button URL', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'placeholder' => '#folyamat', 'label_block' => true, 'condition' => ['button_enabled' => 'yes']]);
        $this->add_control('button_new_tab', ['label' => __('Open in new tab', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'condition' => ['button_enabled' => 'yes']]);
        $this->add_control('button_rel', ['label' => __('Link rel', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true, 'condition' => ['button_enabled' => 'yes']]);
        $this->add_control('button_aria_label', ['label' => __('Accessible label', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true, 'condition' => ['button_enabled' => 'yes']]);
        $this->add_control('button_variant', ['label' => __('Button variant', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SELECT, 'options' => ['pill' => __('Pill', 'herizon-cars'), 'outline' => __('Outline', 'herizon-cars'), 'solid' => __('Solid', 'herizon-cars')], 'default' => 'solid', 'condition' => ['button_enabled' => 'yes']]);
        $this->add_control('button_icon_enabled', ['label' => __('Show icon', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'condition' => ['button_enabled' => 'yes']]);
        $this->add_control('button_icon_source', ['label' => __('Icon source', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SELECT, 'options' => ['library' => __('Font Awesome library', 'herizon-cars'), 'media' => __('Media library (SVG)', 'herizon-cars')], 'default' => 'library', 'condition' => ['button_enabled' => 'yes', 'button_icon_enabled' => 'yes']]);
        $icons = herizon_cars_button_icons();
        $icon_options = [];
        foreach ((array) ($icons['icons'] ?? $icons) as $id => $icon) {
            if (is_array($icon)) { $icon_options[(string) $id] = sanitize_text_field((string) ($icon['label'] ?? $id)) . ' (' . (string) $id . ')'; }
        }
        if (!isset($icon_options['fas:play'])) { $icon_options['fas:play'] = __('Play', 'herizon-cars'); }
        $this->add_control('button_icon', ['label' => __('Icon', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SELECT2, 'options' => $icon_options, 'default' => 'fas:play', 'label_block' => true, 'condition' => ['button_enabled' => 'yes', 'button_icon_enabled' => 'yes', 'button_icon_source' => 'library']]);
        $this->add_control('button_icon_media', ['label' => __('SVG icon', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::MEDIA, 'media_types' => ['image'], 'condition' => ['button_enabled' => 'yes', 'button_icon_enabled' => 'yes', 'button_icon_source' => 'media']]);
        $this->add_control('button_icon_position', ['label' => __('Icon position', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SELECT, 'options' => ['left' => __('Left', 'herizon-cars'), 'right' => __('Right', 'herizon-cars')], 'default' => 'left', 'condition' => ['button_enabled' => 'yes', 'button_icon_enabled' => 'yes']]);
        $this->add_control('button_icon_container', ['label' => __('Use icon container', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'condition' => ['button_enabled' => 'yes', 'button_icon_enabled' => 'yes']]);

        $repeater = new \Elementor\Repeater();
        $repeater->add_control('number', ['label' => __('Number (blank uses order)', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '', 'label_block' => true]);
        $repeater->add_control('title', ['label' => __('Step title', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '', 'label_block' => true]);
        $repeater->add_control('description', ['label' => __('Step description', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => '', 'rows' => 3, 'label_block' => true]);
        $repeater->add_control('image', ['label' => __('Image', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::MEDIA, 'media_types' => ['image']]);
        $repeater->add_control('alt', ['label' => __('Image alternative text', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true]);
        $repeater->add_control('url', ['label' => __('Optional card URL', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true]);
        $repeater->add_control('new_tab', ['label' => __('Open card link in new tab', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes']);
        $repeater->add_control('image_position', ['label' => __('Image position', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SELECT, 'options' => ['center center' => __('Center', 'herizon-cars'), 'center top' => __('Top', 'herizon-cars'), 'center bottom' => __('Bottom', 'herizon-cars'), 'left center' => __('Left', 'herizon-cars'), 'right center' => __('Right', 'herizon-cars')], 'default' => 'center center', 'selectors' => ['{{WRAPPER}} {{CURRENT_ITEM}} .hc-process__image' => 'object-position: {{VALUE}};']]);
        $repeater->add_control('card_color', ['label' => __('Card background color', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} {{CURRENT_ITEM}} .hc-process__card' => 'background: {{VALUE}}; --hc-process-card-bg: {{VALUE}};']]);
        $repeater->add_control('title_color', ['label' => __('Title color', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} {{CURRENT_ITEM}} .hc-process__step-title' => 'color: {{VALUE}};']]);
        $repeater->add_control('description_color', ['label' => __('Description color', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} {{CURRENT_ITEM}} .hc-process__step-description' => 'color: {{VALUE}};']]);
        $repeater->add_control('number_color', ['label' => __('Number color', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} {{CURRENT_ITEM}} .hc-process__number' => 'color: {{VALUE}};']]);
        $repeater->add_control('overlay_color', ['label' => __('Overlay color', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::COLOR, 'alpha' => true, 'selectors' => ['{{WRAPPER}} {{CURRENT_ITEM}} .hc-process__card-overlay' => 'background-image: linear-gradient(180deg, transparent, {{VALUE}} 85%);']]);
        $this->add_control('steps', ['label' => __('Steps', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'default' => [
            ['number' => '01', 'title' => __('Kiválasztás', 'herizon-cars'), 'description' => __('Csak gondosan ellenőrzött autókat ajánlok.', 'herizon-cars')],
            ['number' => '02', 'title' => __('Egyedi keresés', 'herizon-cars'), 'description' => __('Németországból, a te igényeidre szabva.', 'herizon-cars')],
            ['number' => '03', 'title' => __('Átadás', 'herizon-cars'), 'description' => __('Végigkísérlek a teljes folyamaton.', 'herizon-cars')],
        ], 'title_field' => '{{{ number }}} — {{{ title }}}']);
        $this->end_controls_section();

        $this->start_controls_section('layout', ['label' => __('Layout', 'herizon-cars'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('layout_mode', ['label' => __('Content and cards layout', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SELECT, 'options' => ['split' => __('Side by side', 'herizon-cars'), 'stacked' => __('Stacked', 'herizon-cars')], 'default' => 'split', 'tablet_default' => 'stacked', 'mobile_default' => 'stacked', 'selectors_dictionary' => ['split' => 'minmax(0,var(--hc-process-copy-width,56%)) minmax(0,1fr)', 'stacked' => 'minmax(0,1fr)'], 'selectors' => ['{{WRAPPER}} .hc-process__inner' => 'grid-template-columns: {{VALUE}};']]);
        $this->hc_process_slider('min_height', __('Minimum height', 'herizon-cars'), 'section.hc-process', '--hc-process-min-height', 320, 160, 1200);
        $this->hc_process_slider('copy_width', __('Copy width', 'herizon-cars'), '.hc-process__inner', '--hc-process-copy-width', 56, 20, 90, ['%']);
        $this->hc_process_slider('copy_max_width', __('Copy maximum width', 'herizon-cars'), '.hc-process__content', '--hc-process-copy-max-width', 390, 180, 1000);
        $this->hc_process_slider('content_gap', __('Content to cards spacing', 'herizon-cars'), '.hc-process', '--hc-process-gap', 24, 0, 160);
        $this->hc_process_slider('background_width', __('Background image width', 'herizon-cars'), '.hc-process__background', '--hc-process-background-width', 72, 0, 100, ['%'], ['tablet_default' => ['size' => 100, 'unit' => '%'], 'mobile_default' => ['size' => 100, 'unit' => '%']]);
        $this->add_responsive_control('columns', ['label' => __('Card columns', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 1, 'max' => 6, 'step' => 1, 'default' => 3, 'tablet_default' => 3, 'mobile_default' => 1, 'selectors' => ['{{WRAPPER}} .hc-process__steps' => '--hc-process-columns: {{VALUE}};']]);
        $this->hc_process_slider('card_gap', __('Card spacing', 'herizon-cars'), '.hc-process__steps', '--hc-process-gap-cards', 12, 0, 80);
        $this->hc_process_slider('card_min_height', __('Card minimum height', 'herizon-cars'), '.hc-process__card', '--hc-process-card-min-height', 260, 120, 900);
        $this->hc_process_slider('card_padding', __('Card content padding', 'herizon-cars'), '.hc-process__card-content', '--hc-process-card-padding', 24, 0, 120);
        $this->hc_process_slider('cut', __('Diagonal cut', 'herizon-cars'), '.hc-process__card', '--hc-process-cut', 32, 0, 160, ['px'], ['mobile_default' => ['size' => 0, 'unit' => 'px']]);
        $this->hc_process_slider('section_padding', __('Section vertical padding', 'herizon-cars'), 'section.hc-process', '--hc-process-padding', 16, 0, 240);
        $this->add_responsive_control('section_horizontal_padding', ['label' => __('Section horizontal padding', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SLIDER, 'size_units' => ['px', 'vw', 'rem'], 'range' => ['px' => ['min' => 16, 'max' => 240], 'vw' => ['min' => 1, 'max' => 20], 'rem' => ['min' => 1, 'max' => 15]], 'default' => ['size' => 32, 'unit' => 'px'], 'tablet_default' => ['size' => 24, 'unit' => 'px'], 'mobile_default' => ['size' => 16, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} section.hc-process' => 'padding-inline: {{SIZE}}{{UNIT}};']]);
        $this->end_controls_section();

        $this->register_visual_controls();
        $this->register_button_controls();
    }

    private function hc_process_slider(string $name, string $label, string $selector, string $property, float $default, float $min, float $max, array $units = ['px'], array $extra = []): void {
        $range = [];
        foreach ($units as $unit) { $range[$unit] = ['min' => $min, 'max' => $max, 'step' => 1]; }
        $args = ['label' => $label, 'type' => \Elementor\Controls_Manager::SLIDER, 'size_units' => $units, 'range' => $range, 'default' => ['size' => $default, 'unit' => $units[0]], 'selectors' => ['{{WRAPPER}} ' . $selector => $property . ': {{SIZE}}{{UNIT}};']];
        $this->add_responsive_control($name, array_merge($args, $extra));
    }

    private function register_visual_controls(): void {
        $this->start_controls_section('visual', ['label' => __('Section background and cards', 'herizon-cars'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('section_background_color', ['label' => __('Section background color', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} section.hc-process' => 'background-color: {{VALUE}};']]);
        if (class_exists('\Elementor\Group_Control_Background')) {
            $bg_fields = ['background', 'color', 'color_b', 'color_stop', 'color_b_stop', 'gradient_type', 'gradient_angle', 'gradient_position'];
            $this->add_control('background_style_heading', ['type' => \Elementor\Controls_Manager::HEADING, 'label' => __('Background image appearance', 'herizon-cars')]);
            $this->add_group_control(\Elementor\Group_Control_Background::get_type(), ['name' => 'background_image_style', 'label' => __('Background image appearance', 'herizon-cars'), 'types' => ['classic', 'gradient'], 'include' => $bg_fields, 'selector' => '{{WRAPPER}} .hc-process__background']);
            $this->add_control('overlay_style_heading', ['type' => \Elementor\Controls_Manager::HEADING, 'label' => __('Image overlay', 'herizon-cars')]);
            $this->add_group_control(\Elementor\Group_Control_Background::get_type(), ['name' => 'overlay', 'fields_options' => ['color' => ['selectors' => ['{{SELECTOR}}' => 'background: {{VALUE}};']]], 'label' => __('Image overlay', 'herizon-cars'), 'types' => ['classic', 'gradient'], 'selector' => '{{WRAPPER}} .hc-process__overlay']);
            $this->add_control('card_style_heading', ['type' => \Elementor\Controls_Manager::HEADING, 'label' => __('Card background', 'herizon-cars')]);
            $this->add_group_control(\Elementor\Group_Control_Background::get_type(), ['name' => 'card_background', 'label' => __('Card background', 'herizon-cars'), 'types' => ['classic', 'gradient'], 'selector' => '{{WRAPPER}} .hc-process__card']);
        }
        $this->add_control('card_overlay_heading', ['type' => \Elementor\Controls_Manager::HEADING, 'label' => __('Card overlay', 'herizon-cars'), 'separator' => 'before']);
        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), ['name' => 'card_overlay', 'fields_options' => ['color' => ['selectors' => ['{{SELECTOR}}' => 'background: {{VALUE}};']]], 'types' => ['classic', 'gradient'], 'include' => ['background', 'color', 'color_b', 'color_stop', 'color_b_stop', 'gradient_type', 'gradient_angle', 'gradient_position'], 'selector' => '{{WRAPPER}} .hc-process__card-overlay']);
        $this->add_control('card_radius', ['label' => __('Card corner radius', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SLIDER, 'size_units' => ['px', 'rem', '%'], 'range' => ['px' => ['min' => 0, 'max' => 100], 'rem' => ['min' => 0, 'max' => 8], '%' => ['min' => 0, 'max' => 50]], 'default' => ['size' => 0, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .hc-process__card' => '--hc-process-card-radius: {{SIZE}}{{UNIT}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'card_border', 'selector' => '{{WRAPPER}} .hc-process__card']);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => 'card_shadow', 'selector' => '{{WRAPPER}} .hc-process__card']);
        foreach ([['eyebrow', __('Eyebrow', 'herizon-cars'), '.hc-process__eyebrow', '--hc-process-eyebrow-color'], ['title', __('Title', 'herizon-cars'), '.hc-process__title', '--hc-process-title-color'], ['description', __('Description', 'herizon-cars'), '.hc-process__description', '--hc-process-description-color'], ['number', __('Step number', 'herizon-cars'), '.hc-process__number', '--hc-process-number-color'], ['step_title', __('Step title', 'herizon-cars'), '.hc-process__step-title', '--hc-process-step-title-color'], ['step_description', __('Step description', 'herizon-cars'), '.hc-process__step-description', '--hc-process-step-description-color']] as [$name, $label, $selector, $property]) {
            $this->add_control($name . '_style_heading', ['type' => \Elementor\Controls_Manager::HEADING, 'label' => $label, 'separator' => 'before']);
            $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => $name . '_typography', 'selector' => '{{WRAPPER}} ' . $selector]);
            $this->add_control($name . '_color', ['label' => $label . ' ' . __('color', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} ' . $selector => 'color: {{VALUE}}; ' . $property . ': {{VALUE}};']]);
        }
        $this->end_controls_section();
    }

    private function register_button_controls(): void {
        $schema_path = dirname(__DIR__, 2) . '/assets/button-schema.json';
        $schema = is_readable($schema_path) ? json_decode((string) file_get_contents($schema_path), true) : [];
        if (!is_array($schema)) { return; }
        foreach (['normal' => __('Normal', 'herizon-cars'), 'hover' => __('Hover', 'herizon-cars')] as $state => $label) {
            $this->start_controls_section('button_shared_' . $state, ['label' => __('Button', 'herizon-cars') . ' — ' . $label, 'tab' => \Elementor\Controls_Manager::TAB_STYLE, 'condition' => ['button_enabled' => 'yes']]);
            foreach ((array) ($schema['fields'] ?? []) as $field) {
                if (empty($field['key']) || empty($field['type'])) { continue; }
                $key = sanitize_key((string) $field['key']); $id = 'button_' . $state . '_' . $key;
                $target = str_starts_with($key, 'icon') ? '.hc-button__icon' : '.hc-button';
                $selector = '{{WRAPPER}} .hc-process__cta ' . $target;
                $type = $field['type']; $label_text = __((string) ($field['label'] ?? $key), 'herizon-cars');
                if ($type === 'shadow') { $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => $id, 'label' => $label_text, 'selector' => $selector, 'fields_options' => ['__all' => ['selectors' => [], 'render_type' => 'template']]]); continue; }
                if ($type === 'background') {
                    if (class_exists('\Elementor\Group_Control_Background')) { $this->add_group_control(\Elementor\Group_Control_Background::get_type(), ['name' => $id, 'label' => $label_text, 'types' => ['classic', 'gradient'], 'include' => ['background', 'color', 'color_b', 'color_stop', 'color_b_stop', 'gradient_type', 'gradient_angle', 'gradient_position'], 'selector' => $selector, 'fields_options' => ['__all' => ['selectors' => [], 'render_type' => 'template', 'responsive' => true], 'background' => ['default' => ''], 'color' => ['default' => ''], 'color_b' => ['default' => ''], 'gradient_type' => ['device_args' => ['tablet' => ['default' => ''], 'mobile' => ['default' => '']]], 'gradient_position' => ['device_args' => ['tablet' => ['default' => ''], 'mobile' => ['default' => '']]]]]); }
                    continue;
                }
                $args = ['label' => $label_text, 'render_type' => 'template'];
                if ($type === 'color') { $args['type'] = \Elementor\Controls_Manager::COLOR; }
                elseif ($type === 'font') { $args['type'] = \Elementor\Controls_Manager::FONT; }
                elseif ($type === 'select') { $args['type'] = \Elementor\Controls_Manager::SELECT; $args['options'] = ['' => __('Inherit', 'herizon-cars')] + array_map(static fn($v) => __($v, 'herizon-cars'), (array) ($field['options'] ?? [])); }
                elseif ($type === 'length') { $args['type'] = \Elementor\Controls_Manager::SLIDER; $args['size_units'] = ['px', '%', 'em', 'rem', 'vw']; $args['range'] = ['px' => ['min' => $key === 'letterspacing' ? -20 : 0, 'max' => 200], '%' => ['min' => 0, 'max' => 100], 'em' => ['min' => -2, 'max' => 20], 'rem' => ['min' => 0, 'max' => 20], 'vw' => ['min' => 0, 'max' => 50]]; }
                elseif ($type === 'number') { $args['type'] = \Elementor\Controls_Manager::NUMBER; $args['step'] = .1; $args['min'] = .5; $args['max'] = 5; }
                else { continue; }
                $this->add_responsive_control($id, $args);
            }
            $this->end_controls_section();
        }
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $title_tag = in_array(($settings['title_tag'] ?? 'h2'), ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div'], true) ? ($settings['title_tag'] ?? 'h2') : 'h2';
        $background = $this->hc_image((array) ($settings['background_image'] ?? []), '', 'hc-process__background-image');
        $steps = is_array($settings['steps'] ?? null) ? $settings['steps'] : [];
        foreach (['eyebrow', 'title', 'description'] as $inline_control) {
            $this->add_render_attribute($inline_control, 'class', 'hc-process__' . $inline_control);
            $this->add_inline_editing_attributes($inline_control, 'none');
        }
        ?>
        <section class="hc-process">
            <div class="hc-process__background" aria-hidden="true"><?php if ($background !== '') : ?><?php echo $background; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped responsive image markup from hc_image(). ?><?php endif; ?></div>
            <div class="hc-process__overlay" aria-hidden="true"></div>
            <div class="hc-process__inner">
                <div class="hc-process__content">
                    <?php if (!empty($settings['eyebrow'])) : ?><p <?php echo $this->get_render_attribute_string('eyebrow'); ?>><?php echo esc_html($settings['eyebrow']); ?></p><?php endif; ?>
                    <?php if (!empty($settings['title'])) : ?><<?php echo esc_attr($title_tag); ?> <?php echo $this->get_render_attribute_string('title'); ?>><?php echo nl2br(esc_html($settings['title'])); ?></<?php echo esc_attr($title_tag); ?>><?php endif; ?>
                    <?php if (!empty($settings['description'])) : ?><div <?php echo $this->get_render_attribute_string('description'); ?>><p><?php echo nl2br(esc_html($settings['description'])); ?></p></div><?php endif; ?>
                    <?php if (($settings['button_enabled'] ?? 'yes') === 'yes' && function_exists('herizon_cars_button_render')) : ?>
                        <div class="hc-process__cta"><?php echo herizon_cars_button_render([
                            'className' => 'hc-process__button', 'variant' => $settings['button_variant'] ?? 'solid',
                            'text' => $settings['button_text'] ?? '', 'url' => $settings['button_url'] ?? '',
                            'newTab' => ($settings['button_new_tab'] ?? '') === 'yes', 'rel' => $settings['button_rel'] ?? '',
                            'ariaLabel' => $settings['button_aria_label'] ?? '', 'iconEnabled' => ($settings['button_icon_enabled'] ?? 'yes') === 'yes',
                            'iconSource' => $settings['button_icon_source'] ?? 'library', 'icon' => $settings['button_icon'] ?? 'fas:play',
                            'iconId' => absint($settings['button_icon_media']['id'] ?? 0), 'iconPosition' => $settings['button_icon_position'] ?? 'left',
                            'iconContainer' => ($settings['button_icon_container'] ?? 'yes') === 'yes', 'styles' => $this->button_styles($settings),
                        ]); ?></div>
                    <?php endif; ?>
                </div>
                <ol class="hc-process__steps" role="list">
                    <?php foreach ($steps as $index => $step) : if (!is_array($step)) { continue; }
                        $number = trim((string) ($step['number'] ?? '')) ?: sprintf('%02d', $index + 1);
                        $image = $this->hc_image((array) ($step['image'] ?? []), (string) ($step['alt'] ?? ''), 'hc-process__image');
                        $url = $this->safe_url((string) ($step['url'] ?? ''));
                        if (trim((string) ($step['title'] ?? '') . (string) ($step['description'] ?? '')) === '') { $url = ''; }
                        $new_tab = ($step['new_tab'] ?? '') === 'yes' && $url !== '';
                        ?>
                        <li class="hc-process__step elementor-repeater-item-<?php echo esc_attr(sanitize_html_class((string) ($step['_id'] ?? ''))); ?>">
                            <article class="hc-process__card">
                                <?php if ($image !== '') : ?><div class="hc-process__media"<?php if (trim((string) ($step['alt'] ?? '')) === '') : ?> aria-hidden="true"<?php endif; ?>><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped responsive image markup from hc_image(). ?></div><?php endif; ?>
                                <div class="hc-process__card-overlay" aria-hidden="true"></div>
                                <?php if ($url !== '') : ?><a class="hc-process__card-link" href="<?php echo esc_url($url, ['http', 'https', 'mailto', 'tel']); ?>"<?php if ($new_tab) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>><?php else : ?><div class="hc-process__card-content"><?php endif; ?>
                                    <?php if ($url !== '') : ?><div class="hc-process__card-content"><?php endif; ?>
                                        <span class="hc-process__number" aria-hidden="true"><?php echo esc_html($number); ?></span>
                                        <?php if (!empty($step['title'])) : ?><h3 class="hc-process__step-title"><?php echo esc_html($step['title']); ?></h3><?php endif; ?>
                                        <?php if (!empty($step['description'])) : ?><p class="hc-process__step-description"><?php echo esc_html($step['description']); ?></p><?php endif; ?>
                                    </div>
                                <?php if ($url !== '') : ?></a><?php endif; ?>
                            </article>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </section>
        <?php
    }

    private function button_styles(array $settings): array {
        $result = ['normal' => ['desktop' => [], 'tablet' => [], 'mobile' => []], 'hover' => ['desktop' => [], 'tablet' => [], 'mobile' => []]];
        $path = dirname(__DIR__, 2) . '/assets/button-schema.json';
        $schema = is_readable($path) ? json_decode((string) file_get_contents($path), true) : [];
        foreach (['normal', 'hover'] as $state) {
            foreach ((array) ($schema['fields'] ?? []) as $field) {
                $key = (string) ($field['key'] ?? ''); if ($key === '') { continue; }
                $id = 'button_' . $state . '_' . sanitize_key($key); $type = $field['type'] ?? '';
                foreach (['desktop' => '', 'tablet' => '_tablet', 'mobile' => '_mobile'] as $device => $suffix) {
                    $value = '';
                    if ($type === 'background') { $value = $this->background_value($settings, $id, $device); }
                    elseif ($type === 'shadow') { $value = $device === 'desktop' ? $this->shadow_value($settings, $id) : ''; }
                    elseif ($type === 'length') { $value = $this->length_value($settings[$id . $suffix] ?? null); }
                    elseif ($type === 'color') { $value = $this->setting_color($settings, $id . $suffix); }
                    else { $raw = $settings[$id . $suffix] ?? ''; $value = is_scalar($raw) ? trim((string) $raw) : ''; }
                    if ($value !== '') { $result[$state][$device][$key] = $value; }
                }
            }
        }
        return $result;
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


    private function safe_url(string $url): string {
        $url = trim($url);
        if ($url === '' || $url === '#' || preg_match('/[\x00-\x20\x7f]/', $url)) { return ''; }
        return esc_url_raw($url, ['http', 'https', 'mailto', 'tel']);
    }

}
