<?php
/** Contact channels intentionally contain no personal defaults. @package HerizonCars */
defined('ABSPATH') || exit;
$cta = $args['settings']['cta'] ?? [];
$channels = [];
if (!empty($cta['phone'])) {
    $channels[] = ['url' => 'tel:' . preg_replace('/[^+0-9]/', '', $cta['phone']), 'label' => $cta['phone_label']];
}
if (!empty($cta['whatsapp'])) {
    $channels[] = ['url' => 'https://wa.me/' . preg_replace('/[^0-9]/', '', $cta['whatsapp']), 'label' => $cta['whatsapp_label']];
}
if (!empty($cta['email']) && is_email($cta['email'])) {
    $channels[] = ['url' => 'mailto:' . $cta['email'], 'label' => $cta['email_label']];
}
if (!$channels) {
    return;
}
$panel_id = wp_unique_id('hc-contact-');
?>
<div class="hc-sticky-cta" data-hc-sticky-cta hidden>
    <button class="hc-sticky-cta__button" type="button" data-hc-cta-toggle aria-expanded="false" aria-controls="<?php echo esc_attr($panel_id); ?>"><?php echo esc_html($cta['label']); ?></button>
    <div class="hc-sticky-cta__actions" id="<?php echo esc_attr($panel_id); ?>" data-hc-cta-panel hidden inert>
        <p class="hc-sticky-cta__text"><?php echo esc_html($cta['text']); ?></p>
        <?php foreach ($channels as $channel) : ?>
            <a class="hc-sticky-cta__action" href="<?php echo esc_url($channel['url']); ?>"><?php echo esc_html($channel['label']); ?></a>
        <?php endforeach; ?>
    </div>
</div>
