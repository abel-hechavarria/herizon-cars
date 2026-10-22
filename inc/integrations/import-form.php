<?php
/** Scoped enhancements for the native Elementor Pro import form. @package HerizonCars */
defined('ABSPATH') || exit;

/** No delivery switch: activation is a separate, reviewed client task. */
function herizon_cars_import_is_form($record): bool {
    return $record->get_form_settings('form_id') === 'hc_import_request'
        || $record->get_form_settings('form_name') === 'HERIZON egyedi autókeresés';
}

/** Domain validation shared by runtime and boundary tests. Empty vehicle criteria are valid. */
function herizon_cars_import_errors(array $values, int $year): array {
    $errors = [];
    foreach ($values as $id => $value) {
        if (!is_scalar($value) && $value !== null) { $errors[$id] = 'Érvénytelen érték.'; }
    }
    $get = static fn(string $id): string => isset($errors[$id]) ? '' : trim((string) ($values[$id] ?? ''));
    foreach (['first_name','last_name'] as $id) {
        if (sanitize_text_field($get($id)) === '') { $errors[$id] = 'Kérjük, add meg a neved.'; }
    }
    $email = $get('email'); $phone = $get('phone');
    if ($email === '' && $phone === '') {
        $errors['email'] = $errors['phone'] = 'Adj meg legalább egy e-mail-címet vagy telefonszámot.';
    }
    if ($email !== '' && !is_email($email)) { $errors['email'] = 'Kérjük, adj meg érvényes e-mail-címet.'; }
    $digits = preg_replace('/\D/', '', $phone);
    if ($phone !== '' && (!preg_match('/^\+?[0-9 ()\.\/-]+$/D', $phone) || strlen($digits) < 7 || strlen($digits) > 15)) {
        $errors['phone'] = 'Adj meg érvényes telefonszámot, például +36 30 123 4567.';
    }
    foreach (['year_from'=>[1900,$year], 'year_to'=>[1900,$year], 'budget'=>[0,100000000000], 'mileage'=>[0,10000000], 'power'=>[0,10000]] as $id=>$range) {
        $value = $get($id);
        if ($value !== '' && (!preg_match('/^\d+$/D', $value) || (float) $value < $range[0] || (float) $value > $range[1])) {
            $errors[$id] = 'Adj meg érvényes egész számot a megadott tartományban.';
        }
    }
    if ($get('year_from') !== '' && $get('year_to') !== '' && (int) $get('year_from') > (int) $get('year_to')) {
        $errors['year_to'] = 'A felső évhatár nem lehet kisebb az alsónál.';
    }
    foreach (['first_name'=>100,'last_name'=>100,'email'=>254,'phone'=>40,'model'=>120,'city'=>120,'color'=>120,'requirements'=>3000,'message'=>5000] as $id=>$limit) {
        if (mb_strlen($get($id)) > $limit) { $errors[$id] = 'Kérjük, rövidítsd le ezt a szöveget.'; }
    }
    return $errors;
}

add_action('elementor_pro/forms/validation', static function ($record, $handler): void {
    if (!herizon_cars_import_is_form($record)) { return; }
    $values = [];
    foreach ((array) $record->get('fields') as $id=>$field) {
        $raw = $field['raw_value'] ?? $field['value'] ?? '';
        $values[$id] = $raw;
        if (is_scalar($raw)) {
            $clean = in_array($id, ['requirements','message'], true) ? sanitize_textarea_field((string) $raw) : sanitize_text_field((string) $raw);
            $record->update_field($id, 'value', $clean);
        }
    }
    foreach (herizon_cars_import_errors($values, (int) wp_date('Y')) as $id=>$message) { $handler->add_error($id, $message); }
    // Error field guarantees Form_Record::validate() stops before *any* submit action.
    $notice = 'Bemutató mód: a kérés nem lett elküldve és nem lett elmentve.';
    $handler->add_error('demo_notice', $notice);
    $handler->add_error_message($notice);
}, 20, 2);

add_action('elementor/frontend/widget/before_render', static function ($widget): void {
    if ($widget->get_name() !== 'form' || $widget->get_settings_for_display('form_id') !== 'hc_import_request') { return; }
    wp_enqueue_style('herizon-cars-import-form');
    wp_enqueue_script('herizon-cars-import-form');
});
