<?php
/** Adapted from Abel/inc/back-to-top.php. @package HerizonCars */
defined('ABSPATH') || exit;
?>
<button class="hc-back-to-top" type="button" data-hc-back-to-top hidden disabled aria-hidden="true" aria-label="<?php esc_attr_e('Back to top', 'herizon-cars'); ?>">
    <svg class="hc-back-to-top__progress" viewBox="0 0 100 100" aria-hidden="true" focusable="false">
        <circle class="hc-back-to-top__track" cx="50" cy="50" r="42"></circle>
        <circle class="hc-back-to-top__bar" data-hc-progress cx="50" cy="50" r="42"></circle>
    </svg>
    <span class="hc-back-to-top__icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 14.5 12 9l5 5.5"></path></svg></span>
</button>
