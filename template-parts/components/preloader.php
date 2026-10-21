<?php
/** Adapted from Abel/inc/preloader.php. @package HerizonCars */
defined('ABSPATH') || exit;
$text = (string) ($args['text'] ?? '');
$letters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
?>
<div class="hc-preloader" data-hc-preloader hidden inert aria-hidden="true">
    <svg class="hc-preloader__wave" viewBox="0 0 1000 1000" preserveAspectRatio="none" focusable="false" aria-hidden="true">
        <path data-hc-preloader-path d="M0 502S175 272 500 272s500 230 500 230V0H0Z"></path>
    </svg>
    <div class="hc-preloader__heading"><div class="hc-preloader__text" data-hc-preloader-text aria-hidden="true">
        <?php foreach ($letters as $letter) : ?><span><?php echo $letter === ' ' ? '&#160;' : esc_html($letter); ?></span><?php endforeach; ?>
    </div></div>
</div>
