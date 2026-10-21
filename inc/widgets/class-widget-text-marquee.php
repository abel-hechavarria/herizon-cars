<?php
/** Continuous text adapted from AHJ_Widget_Text_Marquee. @package HerizonCars */
defined('ABSPATH') || exit;
class Herizon_Cars_Widget_Text_Marquee extends Herizon_Cars_Widget_Base {
    public function get_name(): string { return 'herizon-text-marquee'; }
    public function get_title(): string { return __('Text marquee', 'herizon-cars'); }
    public function get_icon(): string { return 'eicon-animation-text'; }
    public function get_keywords(): array { return ['herizon', 'text', 'marquee', 'ticker']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Text', 'herizon-cars')]);
        $repeater = new \Elementor\Repeater();
        $repeater->add_control('text', ['label' => __('Text', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true]);
        $this->add_control('items', ['label' => __('Texts', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'default' => [], 'title_field' => '{{{ text }}}']);
        $this->add_control('separator', ['label' => __('Separator', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '✦']);
        $this->end_controls_section();
        $this->hc_marquee_controls('.hc-text-marquee');
        $this->start_controls_section('style', ['label' => __('Style', 'herizon-cars'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->hc_slider('gap', __('Spacing', 'herizon-cars'), '.hc-text-marquee', '--hc-marquee-gap', 32, ['px', 'rem'], 0, 160);
        $this->hc_slider('angle', __('Band angle', 'herizon-cars'), '.hc-text-marquee', '--hc-text-angle', 0, ['deg'], -10, 10);
        $this->hc_slider('padding', __('Band padding', 'herizon-cars'), '.hc-text-marquee', '--hc-text-padding', 24, ['px', 'rem'], 0, 100);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'typography', 'selector' => '{{WRAPPER}} .hc-text-marquee__text, {{WRAPPER}} .hc-text-marquee__separator']);
        $this->add_control('color', ['label' => __('Text color', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .hc-text-marquee' => '--hc-text-color: {{VALUE}};']]);
        $this->add_control('separator_color', ['label' => __('Separator color', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .hc-text-marquee' => '--hc-separator-color: {{VALUE}};']]);
        $this->add_control('background', ['label' => __('Background', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .hc-text-marquee' => 'background-color: {{VALUE}};']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $items = array_filter((array) ($settings['items'] ?? []), static fn($item) => is_array($item) && trim((string) ($item['text'] ?? '')) !== '');
        if (!$items) {
            return;
        }
        ?>
        <div class="<?php echo esc_attr($this->hc_marquee_classes($settings, 'hc-text-marquee hc-marquee')); ?>" data-hc-text-marquee>
            <div class="hc-marquee__viewport"><div class="hc-marquee__track">
                <?php for ($copy = 0; $copy < 2; $copy++) : ?>
                    <div class="hc-marquee__group" data-hc-marquee-group <?php if ($copy) : ?>data-hc-marquee-duplicate aria-hidden="true" inert<?php endif; ?>>
                        <?php foreach ($items as $item) : ?>
                            <span class="hc-marquee__item hc-text-marquee__text"><?php echo esc_html($item['text']); ?></span>
                            <?php if (!empty($settings['separator'])) : ?><span class="hc-text-marquee__separator" aria-hidden="true"><?php echo esc_html($settings['separator']); ?></span><?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endfor; ?>
            </div></div>
            <?php $this->hc_pause_button(); ?>
        </div>
        <?php
    }
}
