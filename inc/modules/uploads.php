<?php
/**
 * Administrator-only media uploads, adapted from Abel inc/security.php.
 * Sanitization runs on temporary files, before WordPress moves them to uploads.
 *
 * @package HerizonCars
 */
if (! defined('ABSPATH')) {
    exit;
}

function herizon_cars_register_uploads(): void
{
    if (! herizon_cars_module_enabled('uploads')) {
        return;
    }
    add_filter('upload_mimes', 'herizon_cars_upload_mimes', 100);
    add_filter('wp_handle_upload_prefilter', 'herizon_cars_upload_prefilter', 100);
    add_filter('wp_handle_sideload_prefilter', 'herizon_cars_upload_prefilter', 100);
    add_filter('wp_check_filetype_and_ext', 'herizon_cars_upload_filetype', 100, 5);
}

function herizon_cars_can_upload_extended_media(): bool
{
    return current_user_can('manage_options') && current_user_can('upload_files');
}

function herizon_cars_upload_mimes(array $mimes): array
{
    // Also remove combined keys granted by another plugin for non-administrators.
    foreach (array_keys($mimes) as $extensions) {
        if (preg_match('/(^|\\|)(svgz?|json)(\\||$)/i', $extensions)) {
            unset($mimes[$extensions]);
        }
    }
    if (herizon_cars_can_upload_extended_media()) {
        $mimes['svg'] = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
        $mimes['json'] = 'application/json';
    }
    return $mimes;
}

function herizon_cars_upload_limit(): int
{
    return max(1, min((int) wp_max_upload_size(), 5 * 1024 * 1024));
}

/**
 * Strict, non-networked SVG subset. Return null when the XML cannot be trusted.
 * Rebuilding a fresh document discards processing instructions, foreign namespace
 * declarations, comments and active content rather than serializing them again.
 */
function herizon_cars_sanitize_svg(string $source): ?string
{
    if (! class_exists('DOMDocument') || ! method_exists('DOMDocument', 'loadXML') || strlen($source) > herizon_cars_upload_limit()
        || preg_match('/<!\\s*(?:DOCTYPE|ENTITY)/i', $source)) {
        return null;
    }
    $previous = libxml_use_internal_errors(true);
    $input = new DOMDocument();
    $input->resolveExternals = false;
    $input->substituteEntities = false;
    try {
        $valid = $input->loadXML($source, LIBXML_NONET);
    } catch (Throwable $error) {
        $valid = false;
    }
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $ns = 'http://www.w3.org/2000/svg';
    if (! $valid || $input->doctype || ! $input->documentElement
        || $input->documentElement->localName !== 'svg' || $input->documentElement->namespaceURI !== $ns) {
        return null;
    }
    $tags = array_flip(['svg', 'g', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'ellipse',
        'text', 'tspan', 'defs', 'symbol', 'use', 'clipPath', 'linearGradient', 'radialGradient',
        'stop', 'mask', 'pattern', 'title', 'desc']);
    $attributes = array_flip(['id', 'class', 'viewBox', 'preserveAspectRatio', 'width', 'height', 'x', 'y',
        'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'dx', 'dy', 'd', 'points', 'transform',
        'fill', 'fill-rule', 'fill-opacity', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
        'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-opacity', 'opacity',
        'clip-path', 'clip-rule', 'mask', 'offset', 'stop-color', 'stop-opacity', 'gradientUnits',
        'gradientTransform', 'spreadMethod', 'fx', 'fy', 'fr', 'patternUnits', 'patternContentUnits',
        'patternTransform', 'maskUnits', 'maskContentUnits', 'clipPathUnits', 'font-family', 'font-size',
        'font-weight', 'font-style', 'text-anchor', 'dominant-baseline', 'letter-spacing', 'role',
        'aria-label', 'aria-labelledby', 'aria-describedby', 'focusable', 'href']);
    $paint = ['fill', 'stroke', 'stop-color'];
    $references = ['clip-path', 'mask'];
    $output = new DOMDocument('1.0', 'UTF-8');
    $copy = static function (DOMElement $element) use (&$copy, $output, $ns, $tags, $attributes, $paint, $references): ?DOMElement {
        if ($element->namespaceURI !== $ns || ! isset($tags[$element->localName])) {
            return null;
        }
        $clean = $output->createElementNS($ns, $element->localName);
        foreach ($element->attributes as $attribute) {
            $name = $attribute->localName;
            $namespace = (string) $attribute->namespaceURI;
            $value = trim($attribute->value);
            $xlink = $namespace === 'http://www.w3.org/1999/xlink' && $name === 'href';
            if (($namespace !== '' && ! $xlink) || ! isset($attributes[$name])
                || preg_match('/[\\x00-\\x1F\\x7F]/', $value)) {
                continue;
            }
            if ($name === 'href') {
                if (! preg_match('/^#[A-Za-z_][A-Za-z0-9_.:-]*$/D', $value)) {
                    continue;
                }
            } elseif (in_array($name, array_merge($paint, $references), true)) {
                $safe_color = in_array($name, $paint, true)
                    && preg_match('/^(?:none|inherit|currentColor|transparent|[A-Za-z]+|#[0-9A-Fa-f]{3,8}|(?:rgb|rgba|hsl|hsla)\\([0-9.,%+\\s-]+\\))$/D', $value);
                $safe_reference = preg_match('/^url\\(#[A-Za-z_][A-Za-z0-9_.:-]*\\)$/D', $value);
                if (! $safe_color && ! $safe_reference && $value !== 'none') {
                    continue;
                }
            } elseif (preg_match('/(?:url\\s*\\(|expression\\s*\\(|javascript\\s*:|data\\s*:|[\\\\<>])/i', $value)) {
                continue;
            }
            if ($name === 'id' && ! preg_match('/^[A-Za-z_][A-Za-z0-9_.:-]*$/D', $value)) {
                continue;
            }
            $clean->setAttribute($name, $value);
        }
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $copied = $copy($child);
                if ($copied) {
                    $clean->appendChild($copied);
                }
            } elseif (($child instanceof DOMText || $child instanceof DOMCdataSection)
                && in_array($element->localName, ['title', 'desc', 'text', 'tspan'], true)) {
                $clean->appendChild($output->createTextNode($child->textContent));
            }
        }
        return $clean;
    };
    $root = $copy($input->documentElement);
    if (! $root) {
        return null;
    }
    $output->appendChild($root);
    $ids = [];
    foreach ($output->getElementsByTagName('*') as $element) {
        if ($element->hasAttribute('id')) {
            $id = $element->getAttribute('id');
            if (isset($ids[$id])) {
                $element->removeAttribute('id');
            } else {
                $ids[$id] = true;
            }
        }
    }
    foreach ($output->getElementsByTagName('*') as $element) {
        foreach (['href', 'fill', 'stroke', 'stop-color', 'clip-path', 'mask'] as $name) {
            $value = $element->getAttribute($name);
            if (preg_match('/^(?:#|url\\(#)([A-Za-z_][A-Za-z0-9_.:-]*)(?:\\))?$/D', $value, $match)
                && ($name === 'href' || str_starts_with($value, 'url(')) && ! isset($ids[$match[1]])) {
                $element->removeAttribute($name);
            }
        }
    }
    $result = $output->saveXML($root);
    return is_string($result) && $result !== '' ? $result : null;
}

/** @return array{content?:string,name?:string,type?:string,error?:string} */
function herizon_cars_validate_media_file(string $file, string $filename): array
{
    if (! herizon_cars_can_upload_extended_media()) {
        return ['error' => __('Only administrators can upload SVG or JSON files.', 'herizon-cars')];
    }
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (! in_array($extension, ['svg', 'svgz', 'json'], true) || ! is_file($file) || ! is_readable($file)) {
        return ['error' => __('The upload cannot be read.', 'herizon-cars')];
    }
    $limit = herizon_cars_upload_limit();
    $source = @file_get_contents($file, false, null, 0, $limit + 1);
    if (! is_string($source) || $source === '' || strlen($source) > $limit) {
        return ['error' => __('The upload is empty or exceeds the safe size limit.', 'herizon-cars')];
    }
    if ($extension === 'json') {
        json_decode($source);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => __('The upload must contain valid JSON.', 'herizon-cars')];
        }
        return ['content' => $source, 'name' => $filename, 'type' => 'application/json'];
    }
    if ($extension === 'svgz') {
        if (! function_exists('gzdecode')) {
            return ['error' => __('Compressed SVG support is unavailable.', 'herizon-cars')];
        }
        $source = @gzdecode($source, $limit + 1);
        if (! is_string($source) || strlen($source) > $limit) {
            return ['error' => __('The compressed SVG is invalid or too large.', 'herizon-cars')];
        }
    }
    $sanitized = herizon_cars_sanitize_svg($source);
    if ($sanitized === null) {
        return ['error' => __('The SVG is invalid or cannot be sanitized safely.', 'herizon-cars')];
    }
    $filename = preg_replace('/\\.svgz?$/i', '.svg', $filename);
    return ['content' => $sanitized, 'name' => $filename, 'type' => 'image/svg+xml'];
}

function herizon_cars_upload_prefilter(array $upload): array
{
    $extension = strtolower(pathinfo((string) ($upload['name'] ?? ''), PATHINFO_EXTENSION));
    $special_mime = in_array(strtolower((string) ($upload['type'] ?? '')), ['image/svg+xml', 'application/json'], true);
    if (! in_array($extension, ['svg', 'svgz', 'json'], true)) {
        if ($special_mime) {
            $upload['error'] = __('The file extension does not match its media type.', 'herizon-cars');
        }
        return $upload;
    }
    if (! empty($upload['error'])) {
        return $upload;
    }
    $checked = herizon_cars_validate_media_file((string) ($upload['tmp_name'] ?? ''), (string) $upload['name']);
    if (isset($checked['error'])) {
        $upload['error'] = $checked['error'];
        return $upload;
    }
    if ($extension !== 'json' && @file_put_contents($upload['tmp_name'], $checked['content'], LOCK_EX) !== strlen($checked['content'])) {
        $upload['error'] = __('The sanitized SVG could not be saved.', 'herizon-cars');
        return $upload;
    }
    $upload['name'] = $checked['name'];
    $upload['type'] = $checked['type'];
    $upload['size'] = strlen($checked['content']);
    return $upload;
}

/** The filetype filter is also a guard for entry points bypassing upload prefilters. */
function herizon_cars_upload_filetype(array $data, string $file, string $filename, $mimes = null, $real_mime = null): array
{
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (! in_array($extension, ['svg', 'svgz', 'json'], true)) {
        return $data;
    }
    $checked = herizon_cars_validate_media_file($file, $filename);
    if (isset($checked['error']) || ($extension !== 'json'
        && @file_put_contents($file, $checked['content'], LOCK_EX) !== strlen($checked['content']))) {
        return array_merge($data, ['ext' => false, 'type' => false, 'proper_filename' => false]);
    }
    return array_merge($data, [
        'ext' => $extension === 'json' ? 'json' : 'svg',
        'type' => $checked['type'],
        'proper_filename' => $checked['name'] !== $filename ? $checked['name'] : false,
    ]);
}
