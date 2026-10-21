<?php
/** Shared native controls, adapted from abel-web-core marquee widgets. @package HerizonCars */
defined('ABSPATH') || exit;

abstract class Herizon_Cars_Widget_Base extends \Elementor\Widget_Base {
    public function get_categories(): array { return ['herizon-cars']; }
    public function get_style_depends(): array { return ['herizon-cars-widgets']; }
    public function get_script_depends(): array { return ['herizon-cars-widgets']; }

    protected function hc_slider(string $name, string $label, string $selector, string $property, float $default, array $units = ['px'], float $min = 0, float $max = 200): void {
        $range = [];
        foreach ($units as $unit) {
            $range[$unit] = ['min' => $min, 'max' => $max, 'step' => 1];
        }
        $this->add_responsive_control($name, [
            'label' => $label, 'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => $units, 'range' => $range,
            'default' => ['size' => $default, 'unit' => $units[0]],
            'selectors' => ['{{WRAPPER}} ' . $selector => $property . ': {{SIZE}}{{UNIT}};'],
        ]);
    }

    protected function hc_marquee_controls(string $selector): void {
        $this->start_controls_section('animation', ['label' => __('Animation', 'herizon-cars')]);
        $this->add_control('duration', [
            'label' => __('Cycle duration (seconds)', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::NUMBER,
            'min' => 5, 'max' => 180, 'default' => 40,
            'selectors' => ['{{WRAPPER}} ' . $selector => '--hc-marquee-duration: {{VALUE}}s;'],
        ]);
        $this->add_control('reverse', ['label' => __('Reverse direction', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes']);
        $this->add_control('pause_hover', ['label' => __('Pause on hover', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes']);
        $this->add_control('fade', ['label' => __('Fade edges', 'herizon-cars'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes']);
        $this->end_controls_section();
    }

    protected function hc_pause_button(): void {
        ?>
        <button type="button" class="hc-marquee__pause" data-hc-marquee-pause
            data-label-pause="<?php esc_attr_e('Pause animation', 'herizon-cars'); ?>"
            data-label-play="<?php esc_attr_e('Resume animation', 'herizon-cars'); ?>"
            aria-pressed="false"><?php esc_html_e('Pause animation', 'herizon-cars'); ?></button>
        <?php
    }

    protected function hc_marquee_classes(array $settings, string $base): string {
        return $base . (!empty($settings['reverse']) ? ' is-reverse' : '')
            . (!empty($settings['pause_hover']) ? ' hc-marquee--pause-hover' : '')
            . (!empty($settings['fade']) ? ' hc-marquee--fade' : '');
    }

    /** Respect media removal and preserve WordPress responsive image markup. */
    protected function hc_image(array $media, string $alt, string $class): string {
        $id = absint($media['id'] ?? 0);
        if ($id) {
            if (get_post_type($id) !== 'attachment' || !wp_attachment_is_image($id)) {
                return '';
            }
            $alt = $alt !== '' ? $alt : (string) get_post_meta($id, '_wp_attachment_image_alt', true);
            return wp_get_attachment_image($id, 'large', false, ['class' => $class, 'alt' => $alt, 'loading' => 'lazy']);
        }
        $url = esc_url($media['url'] ?? '');
        return $url !== '' ? '<img class="' . esc_attr($class) . '" src="' . $url . '" alt="' . esc_attr($alt) . '" loading="lazy" decoding="async">' : '';
    }
}
