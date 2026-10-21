<?php
/** Manual gallery adapted from AHJ elegant gallery layout, without ACF. @package HerizonCars */
defined('ABSPATH') || exit;
class Herizon_Cars_Widget_Elegant_Gallery extends Herizon_Cars_Widget_Base {
    public function get_name(): string { return 'herizon-elegant-gallery'; }
    public function get_title(): string { return __('Expandable gallery', 'herizon-cars'); }
    public function get_icon(): string { return 'eicon-gallery-justified'; }
    public function get_keywords(): array { return ['herizon', 'gallery', 'images', 'accordion']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label' => __('Images', 'herizon-cars')]);
        $repeater = new \Elementor\Repeater();
        $repeater->add_control('image', ['label' => __('Image', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::MEDIA]);
        $repeater->add_control('alt', ['label' => __('Alternative text', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true]);
        $repeater->add_control('caption', ['label' => __('Caption', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true]);
        $this->add_control('items', ['label' => __('Images', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'default' => [], 'title_field' => '{{{ alt }}}']);
        $this->add_responsive_control('limit', ['label' => __('Visible images', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 1, 'max' => 24, 'default' => 6, 'tablet_default' => 4, 'mobile_default' => 2]);
        $this->end_controls_section();
        $this->start_controls_section('style', ['label' => __('Layout', 'herizon-cars'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->hc_slider('height', __('Image height', 'herizon-cars'), '.hc-gallery', '--hc-gallery-height', 500, ['px', 'vh'], 100, 900);
        $this->hc_slider('item_width', __('Item width', 'herizon-cars'), '.hc-gallery', '--hc-gallery-item-width', 20, ['%', 'px', 'vw'], 5, 700);
        $this->hc_slider('active_width', __('Expanded width', 'herizon-cars'), '.hc-gallery', '--hc-gallery-active-width', 60, ['%', 'px', 'vw'], 10, 1400);
        $this->hc_slider('gap', __('Spacing', 'herizon-cars'), '.hc-gallery', '--hc-gallery-gap', 8, ['px', 'rem'], 0, 80);
        $this->hc_slider('radius', __('Corner radius', 'herizon-cars'), '.hc-gallery', '--hc-gallery-radius', 0, ['px', 'rem'], 0, 80);
        $this->hc_slider('max_width', __('Maximum width', 'herizon-cars'), '.hc-gallery', '--hc-gallery-max-width', 100, ['%', 'px', 'vw'], 10, 1920);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $items = [];
        foreach ((array) ($settings['items'] ?? []) as $item) {
            $image = $this->hc_image((array) ($item['image'] ?? []), (string) ($item['alt'] ?? ''), 'hc-gallery__image');
            if ($image !== '') {
                $items[] = ['image' => $image, 'alt' => (string) ($item['alt'] ?? ''), 'caption' => (string) ($item['caption'] ?? '')];
            }
        }
        if (!$items) {
            return;
        }
        $limit = static fn($value, $default) => max(1, min(24, absint($value ?: $default)));
        ?>
        <div class="hc-gallery" data-hc-gallery
            data-limit-desktop="<?php echo esc_attr((string) $limit($settings['limit'] ?? 6, 6)); ?>"
            data-limit-tablet="<?php echo esc_attr((string) $limit($settings['limit_tablet'] ?? 4, 4)); ?>"
            data-limit-mobile="<?php echo esc_attr((string) $limit($settings['limit_mobile'] ?? 2, 2)); ?>">
            <?php foreach ($items as $index => $item) : $caption_id = wp_unique_id('hc-gallery-caption-'); ?>
                <figure class="hc-gallery__item">
                    <button class="hc-gallery__trigger" type="button" data-hc-gallery-trigger aria-expanded="false"
                        aria-label="<?php echo esc_attr($item['alt'] ?: sprintf(__('Expand image %d', 'herizon-cars'), $index + 1)); ?>"
                        <?php if ($item['caption'] !== '') : ?>aria-describedby="<?php echo esc_attr($caption_id); ?>"<?php endif; ?>>
                        <?php echo $item['image']; ?>
                    </button>
                    <?php if ($item['caption'] !== '') : ?><figcaption class="hc-gallery__caption" id="<?php echo esc_attr($caption_id); ?>"><?php echo esc_html($item['caption']); ?></figcaption><?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
