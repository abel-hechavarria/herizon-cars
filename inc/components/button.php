<?php
/** Shared HCars Button design contract, validation and renderer. @package HerizonCars */
if (!defined('ABSPATH')) { exit; }

function herizon_cars_button_schema(): array {
    static $schema;
    if (!isset($schema)) {
        $data = json_decode((string) file_get_contents(HERIZON_CARS_PATH . 'assets/button-schema.json'), true);
        $schema = is_array($data) ? $data : ['fields' => [], 'presets' => []];
    }
    return $schema;
}

function herizon_cars_button_icons(): array {
    static $icons;
    if (!isset($icons)) {
        $path = HERIZON_CARS_PATH . 'assets/button-icons.json';
        $data = is_readable($path) ? json_decode((string) file_get_contents($path), true) : [];
        $icons = is_array($data) ? $data : [];
    }
    return $icons;
}

function herizon_cars_button_kebab(string $name): string {
    return strtolower((string) preg_replace('/[A-Z]/', '-$0', $name));
}

/** Only a typed CSS value is accepted; never declarations, resource URLs or HTML. */
function herizon_cars_button_style_value(array $field, $value): ?string {
    if (!is_scalar($value) || is_bool($value)) { return null; }
    $value = trim((string) $value);
    if ($value === '' || strlen($value) > 350 || preg_match('/[;{}<>\\\\\x00-\x1f\x7f]|url\s*\(|expression\s*\(|javascript\s*:|data\s*:|@/i', $value)) { return null; }
    $color = static fn(string $color): bool => (bool) preg_match('/^(?:#[a-f0-9]{3,4}|#[a-f0-9]{6}|#[a-f0-9]{8}|[a-z]+|(?:rgb|rgba|hsl|hsla)\([0-9.,%+\s-]+\)|var\(--e-global-color-[a-z0-9_-]+\))$/iD', $color);
    switch ($field['type'] ?? '') {
        case 'color': return $color($value) ? $value : null;
        case 'background':
            if ($color($value)) { return $value; }
            if (!preg_match('/^(?:linear-gradient|radial-gradient)\([a-zA-Z0-9#.,%()+\s-]+\)$/D', $value)) { return null; }
            $depth = 0;
            foreach (str_split($value) as $char) { if ($char === '(') { $depth++; } elseif ($char === ')' && --$depth < 0) { return null; } }
            return $depth === 0 ? $value : null;
        case 'select': return array_key_exists($value, $field['options'] ?? []) ? $value : null;
        case 'length':
            if (($field['key'] ?? '') === 'width' && in_array($value, ['auto', 'fit-content', 'max-content', 'min-content'], true)) { return $value; }
            if ($value === '0') { return '0px'; }
            if (!preg_match('/^(-?(?:\d+(?:\.\d+)?|\.\d+))(px|rem|em|%|vw|vh|ch)$/D', $value, $match)) { return null; }
            $number = (float) $match[1];
            if (abs($number) > 10000 || ($number < 0 && ($field['key'] ?? '') !== 'letterSpacing')) { return null; }
            return $value;
        case 'number': return is_numeric($value) && (float) $value >= .5 && (float) $value <= 5 ? (string) (float) $value : null;
        case 'font': return preg_match('/^[\p{L}\p{N}\s,\x27"_-]{1,160}$/uD', $value) ? $value : null;
        case 'shadow':
            return $value === 'none' || preg_match('/^(?:inset\s+)?[-0-9.a-zA-Z#(),%\s]+$/D', $value) ? $value : null;
        default: return null;
    }
}

function herizon_cars_button_normalize(array $input): array {
    $text = static fn($value): string => is_scalar($value) ? sanitize_text_field((string) $value) : '';
    $bool = static fn($value): bool => in_array($value, [true, 1, '1', 'true', 'yes', 'on'], true);
    $variant = $input['variant'] ?? 'pill';
    $icon = $text($input['icon'] ?? 'fas:play');
    if (!preg_match('/^(fas|far|fab):[a-z0-9-]+$/D', $icon) || !isset(herizon_cars_button_icons()[$icon])) { $icon = 'fas:play'; }
    $rel = preg_split('/\s+/', strtolower($text($input['rel'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
    $rel = array_values(array_intersect($rel ?: [], ['nofollow', 'sponsored', 'ugc', 'noopener', 'noreferrer', 'external']));
    $new_tab = $bool($input['newTab'] ?? false);
    if ($new_tab) { $rel = array_merge($rel, ['noopener', 'noreferrer']); }
    $styles = [];
    foreach (['normal', 'hover'] as $state) {
        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            $source = $input['styles'][$state][$device] ?? [];
            if (!is_array($source)) { continue; }
            foreach (herizon_cars_button_schema()['fields'] as $field) {
                if (!array_key_exists($field['key'], $source)) { continue; }
                $value = herizon_cars_button_style_value($field, $source[$field['key']]);
                if ($value !== null) { $styles[$state][$device][$field['key']] = $value; }
            }
        }
    }
    $url = is_scalar($input['url'] ?? '') ? trim((string) ($input['url'] ?? '')) : '';
    // An empty/hash-only URL does not create a fake interactive control.
    if ($url === '#' || preg_match('/[\x00-\x20\x7f]/', $url)) { $url = ''; }
    $url = $url !== '' ? esc_url_raw($url, ['http', 'https', 'mailto', 'tel']) : '';
    $result = [
        'variant' => in_array($variant, ['pill', 'outline', 'solid'], true) ? $variant : 'pill',
        'text' => $text($input['text'] ?? __('Button', 'herizon-cars')),
        'url' => $url, 'newTab' => $new_tab,
        'rel' => implode(' ', array_unique($rel)), 'ariaLabel' => $text($input['ariaLabel'] ?? ''),
        'iconEnabled' => array_key_exists('iconEnabled', $input) ? $bool($input['iconEnabled']) : true,
        'iconSource' => ($input['iconSource'] ?? '') === 'media' ? 'media' : 'library',
        'icon' => $icon, 'iconId' => is_scalar($input['iconId'] ?? 0) ? absint($input['iconId'] ?? 0) : 0,
        'iconPosition' => ($input['iconPosition'] ?? '') === 'right' ? 'right' : 'left',
        'iconContainer' => array_key_exists('iconContainer', $input) ? $bool($input['iconContainer']) : true,
        'duration' => is_numeric($input['duration'] ?? 200) ? max(0, min(2000, (float) ($input['duration'] ?? 200))) : 200,
        'hoverMove' => is_numeric($input['hoverMove'] ?? 3) ? max(-30, min(30, (float) ($input['hoverMove'] ?? 3))) : 3,
        'styles' => $styles,
    ];
    if ($result['text'] === '' && $result['ariaLabel'] === '') { $result['text'] = __('Button', 'herizon-cars'); }
    return $result;
}

function herizon_cars_button_style_attributes(array $attributes): string {
    $declarations = ['--hc-button-duration:' . $attributes['duration'] . 'ms', '--hc-button-hover-move:' . $attributes['hoverMove'] . 'px'];
    foreach ($attributes['styles'] as $state => $devices) {
        foreach ($devices as $device => $fields) {
            $prefix = '--hc-button-' . ($device !== 'desktop' ? $device . '-' : '') . ($state === 'hover' ? 'hover-' : '');
            foreach ($fields as $field => $value) { $declarations[] = $prefix . herizon_cars_button_kebab($field) . ':' . $value; }
        }
    }
    return implode(';', $declarations);
}

function herizon_cars_button_icon(array $attributes): string {
    if (!$attributes['iconEnabled']) { return ''; }
    if ($attributes['iconSource'] === 'media') {
        $id = $attributes['iconId'];
        if (!$id || get_post_type($id) !== 'attachment' || get_post_mime_type($id) !== 'image/svg+xml') { return ''; }
        $path = get_attached_file($id);
        if (!is_string($path) || !is_file($path) || !is_readable($path)) { return ''; }
        if (!function_exists('herizon_cars_sanitize_svg')) { require_once HERIZON_CARS_PATH . 'inc/modules/uploads.php'; }
        if (filesize($path) > herizon_cars_upload_limit()) { return ''; }
        $svg = herizon_cars_sanitize_svg((string) file_get_contents($path));
        if (!$svg) { return ''; }
        $dom = new DOMDocument();
        if (!$dom->loadXML($svg, LIBXML_NONET)) { return ''; }
        $root = $dom->documentElement;
        $root->setAttribute('aria-hidden', 'true'); $root->setAttribute('focusable', 'false');
        $root->removeAttribute('width'); $root->removeAttribute('height');
        // Button icons are monochrome. Preserve explicit "none" for line icons.
        foreach ($dom->getElementsByTagName('*') as $element) {
            foreach (['fill', 'stroke'] as $paint) {
                if ($element->hasAttribute($paint) && $element->getAttribute($paint) !== 'none') { $element->setAttribute($paint, 'currentColor'); }
            }
        }
        return (string) $dom->saveXML($root);
    }
    $icon = herizon_cars_button_icons()[$attributes['icon']] ?? null;
    if (!$icon) { return ''; }
    $html = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . (int) $icon['width'] . ' ' . (int) $icon['height'] . '" aria-hidden="true" focusable="false">';
    foreach ($icon['paths'] as $path) { $html .= '<path fill="currentColor" d="' . esc_attr($path) . '"/>'; }
    return $html . '</svg>';
}

function herizon_cars_button_render(array $input): string {
    $attributes = herizon_cars_button_normalize($input);
    if (function_exists('wp_enqueue_style')) { wp_enqueue_style('herizon-cars-button'); }
    $classes = ['hc-button', 'hc-button--' . $attributes['variant']];
    if ($attributes['iconPosition'] === 'right') { $classes[] = 'hc-button--icon-right'; }
    if (!$attributes['iconContainer']) { $classes[] = 'hc-button--no-icon-container'; }
    $wrapper_classes = ['hc-button-wrap'];
    foreach (preg_split('/\s+/', is_string($input['className'] ?? null) ? $input['className'] : '', -1, PREG_SPLIT_NO_EMPTY) ?: [] as $class) { $wrapper_classes[] = sanitize_html_class($class); }
    $id = is_string($input['anchor'] ?? null) && $input['anchor'] !== '' ? ' id="' . esc_attr(sanitize_title($input['anchor'])) . '"' : '';
    $html = '<div' . $id . ' class="' . esc_attr(implode(' ', $wrapper_classes)) . '" style="' . esc_attr(herizon_cars_button_style_attributes($attributes)) . '">';
    $tag = $attributes['url'] !== '' ? 'a' : 'span';
    $html .= '<' . $tag . ' class="' . esc_attr(implode(' ', $classes)) . '"';
    if ($tag === 'a') {
        $html .= ' href="' . esc_url($attributes['url'], ['http', 'https', 'mailto', 'tel']) . '"';
        if ($attributes['newTab']) { $html .= ' target="_blank"'; }
        if ($attributes['rel'] !== '') { $html .= ' rel="' . esc_attr($attributes['rel']) . '"'; }
    } else { $html .= ' aria-disabled="true"'; }
    if ($attributes['ariaLabel'] !== '') { $html .= ' aria-label="' . esc_attr($attributes['ariaLabel']) . '"'; }
    $html .= '>';
    $icon = herizon_cars_button_icon($attributes);
    if ($icon !== '') { $html .= '<span class="hc-button__icon" aria-hidden="true">' . $icon . '</span>'; }
    $html .= '<span class="hc-button__label">' . esc_html($attributes['text']) . '</span>';
    return $html . '</' . $tag . '></div>';
}
