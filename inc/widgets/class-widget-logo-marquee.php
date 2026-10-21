<?php
/** Manual logo marquee adapted from AHJ_Widget_Logo_Marquee. @package HerizonCars */
defined('ABSPATH') || exit;
class Herizon_Cars_Widget_Logo_Marquee extends Herizon_Cars_Widget_Base {
    public function get_name(): string { return 'herizon-logo-marquee'; }
    public function get_title(): string { return __('Logo marquee', 'herizon-cars'); }
    public function get_icon(): string { return 'eicon-carousel'; }
    public function get_keywords(): array { return ['herizon', 'logos', 'marquee', 'carousel']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Logos', 'herizon-cars')]);
        $repeater = new \Elementor\Repeater();
        $repeater->add_control('image', ['label' => __('Image', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::MEDIA]);
        $repeater->add_control('alt', ['label' => __('Alternative text', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT]);
        $repeater->add_control('link', ['label' => __('Link', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::URL, 'options' => ['url', 'is_external', 'nofollow']]);
        $this->add_control('items', ['label' => __('Logos', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'default' => [], 'title_field' => '{{{ alt }}}']);
        $this->end_controls_section();
        $this->hc_marquee_controls('.hc-logo-marquee');
        $this->start_controls_section('style', ['label' => __('Style', 'herizon-cars'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->hc_slider('gap', __('Spacing', 'herizon-cars'), '.hc-logo-marquee', '--hc-marquee-gap', 48, ['px', 'rem'], 0, 150);
        $this->hc_slider('logo_height', __('Logo height', 'herizon-cars'), '.hc-logo-marquee', '--hc-logo-height', 56, ['px', 'rem'], 10, 240);
        $this->add_control('grayscale', ['label' => __('Grayscale logos', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes']);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $items = [];
        foreach ((array) ($settings['items'] ?? []) as $item) {
            $image = $this->hc_image((array) ($item['image'] ?? []), (string) ($item['alt'] ?? ''), 'hc-logo-marquee__image');
            if ($image !== '') {
                $items[] = ['image' => $image, 'link' => (array) ($item['link'] ?? [])];
            }
        }
        if (!$items) {
            return;
        }
        $classes = $this->hc_marquee_classes($settings, 'hc-logo-marquee hc-marquee') . (!empty($settings['grayscale']) ? ' hc-marquee--grayscale' : '');
        ?>
        <div class="<?php echo esc_attr($classes); ?>" data-hc-logo-marquee>
            <div class="hc-marquee__viewport"><div class="hc-marquee__track">
                <?php for ($copy = 0; $copy < 2; $copy++) : ?>
                    <div class="hc-marquee__group" data-hc-marquee-group <?php if ($copy) : ?>data-hc-marquee-duplicate aria-hidden="true" inert<?php endif; ?>>
                        <?php foreach ($items as $item) : $link = $item['link']; ?>
                            <div class="hc-marquee__item hc-logo-marquee__item">
                                <?php if (!$copy && !empty($link['url'])) : ?>
                                    <a href="<?php echo esc_url($link['url']); ?>" <?php if (!empty($link['is_external'])) : ?>target="_blank"<?php endif; ?> rel="<?php echo esc_attr('noopener' . (!empty($link['nofollow']) ? ' nofollow' : '')); ?>"><?php echo $item['image']; // Escaped by hc_image. ?></a>
                                <?php else : echo $item['image']; endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endfor; ?>
            </div></div>
        </div>
        <?php
    }
}
