<?php
/** Selected Abel performance/SEO/helpers adapted for Herizon Cars. */
if (! defined('ABSPATH')) {
    exit;
}

function herizon_cars_register_utilities(): void
{
    if (herizon_cars_module_enabled('https_normalization')) {
        add_action('template_redirect', 'herizon_cars_start_https_buffer', 99);
    }
    if (herizon_cars_module_enabled('jquery_ui')) {
        add_action('wp_enqueue_scripts', 'herizon_cars_dequeue_unused_jquery_ui', 999);
        add_action('wp_print_footer_scripts', 'herizon_cars_dequeue_unused_jquery_ui', 1);
    }
    if (herizon_cars_module_enabled('image_sizes')) {
        add_filter('intermediate_image_sizes_advanced', 'herizon_cars_image_sizes');
        add_filter('big_image_size_threshold', 'herizon_cars_big_image_threshold');
    }
    if (herizon_cars_module_enabled('shortlinks')) {
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('template_redirect', 'wp_shortlink_header', 11);
    }
    if (herizon_cars_module_enabled('self_pingbacks')) {
        add_action('pre_ping', 'herizon_cars_remove_self_pingbacks');
    }
    if (herizon_cars_module_enabled('body_class')) {
        add_filter('body_class', 'herizon_cars_slug_body_class');
    }
    if (herizon_cars_module_enabled('site_icon')) {
        remove_action('wp_head', 'wp_site_icon', 99);
        remove_action('login_head', 'wp_site_icon', 99);
    }
    if (herizon_cars_module_enabled('year')) {
        add_shortcode('herizon_year', 'herizon_cars_year_shortcode');
        add_shortcode('ahj_year', 'herizon_cars_year_shortcode');
    }
}

function herizon_cars_public_html_request(): bool
{
    return is_ssl() && ! is_admin() && ! wp_doing_ajax() && ! wp_is_json_request()
        && ! (defined('REST_REQUEST') && REST_REQUEST)
        && ! (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST)
        && ! is_feed() && ! is_trackback() && ! is_robots()
        && ! isset($_GET['elementor-preview']) && ! isset($_GET['elementor_library']);
}

function herizon_cars_start_https_buffer(): void
{
    static $started = false;
    if (! $started && herizon_cars_public_html_request()) {
        $started = true;
        ob_start('herizon_cars_https_response_buffer');
    }
}

/** Fail closed for downloads, compressed bytes, APIs and non-success responses. */
function herizon_cars_is_html_response(array $headers, int $status): bool
{
    if ($status < 200 || $status >= 300 || in_array($status, [204, 205, 206], true)) {
        return false;
    }
    $html = false;
    foreach ($headers as $header) {
        if (stripos($header, 'Content-Disposition:') === 0 || stripos($header, 'Content-Encoding:') === 0) {
            return false;
        }
        if (stripos($header, 'Content-Type:') === 0) {
            $html = (bool) preg_match('/^Content-Type:\\s*text\\/html(?:\\s*;|\\s*$)/i', $header);
        }
    }
    return $html;
}

function herizon_cars_https_response_buffer(string $html): string
{
    if (! herizon_cars_public_html_request()
        || ! herizon_cars_is_html_response(headers_list(), (int) http_response_code())) {
        return $html;
    }
    return herizon_cars_normalize_http_urls($html);
}

/** Match full URLs first; comparing their parsed host avoids prefix collisions. */
function herizon_cars_normalize_http_urls(string $html): string
{
    $host = wp_parse_url(home_url('/'), PHP_URL_HOST);
    if (! is_string($host) || $host === '') {
        return $html;
    }
    $pattern = '~(?<![A-Za-z0-9+.:/\\\\-])http:(?://|\\\\/\\\\/)[^\\s<>"\x27]+~i';
    $result = preg_replace_callback($pattern, static function (array $match) use ($host): string {
        $url = str_replace('\\/', '/', $match[0]);
        $candidate = wp_parse_url($url, PHP_URL_HOST);
        if (! is_string($candidate) || strcasecmp($candidate, $host) !== 0
            || wp_parse_url($url, PHP_URL_USER) !== null || wp_parse_url($url, PHP_URL_PASS) !== null) {
            return $match[0];
        }
        return 'https:' . substr($match[0], 5);
    }, $html);
    return is_string($result) ? $result : $html;
}

/** Resolve queued and already printed dependencies transitively, including aliases. */
function herizon_cars_dependency_is_required($registry, string $target): bool
{
    $roots = array_unique(array_merge($registry->queue ?? [], $registry->done ?? [], $registry->to_do ?? []));
    foreach ($roots as $handle) {
        if ($handle === $target) {
            continue;
        }
        $seen = [];
        $visit = static function (string $name) use (&$visit, &$seen, $registry, $target): bool {
            if (isset($seen[$name])) {
                return false;
            }
            $seen[$name] = true;
            foreach (($registry->registered[$name]->deps ?? []) as $dependency) {
                if ($dependency === $target || $visit($dependency)) {
                    return true;
                }
            }
            return false;
        };
        if ($visit($handle)) {
            return true;
        }
    }
    return false;
}

function herizon_cars_dequeue_unused_jquery_ui(): void
{
    if (is_admin() || ! is_front_page() || isset($_GET['elementor-preview'])) {
        return;
    }
    $scripts = wp_scripts();
    if (! herizon_cars_dependency_is_required($scripts, 'jquery-ui-core')
        && ! in_array('jquery-ui-core', $scripts->done, true)) {
        wp_dequeue_script('jquery-ui-core');
    }
    $styles = wp_styles();
    if (! herizon_cars_dependency_is_required($styles, 'jquery-ui-core')
        && ! in_array('jquery-ui-core', $styles->done, true)) {
        wp_dequeue_style('jquery-ui-core');
    }
}

function herizon_cars_image_sizes(array $sizes): array
{
    unset($sizes['medium_large'], $sizes['1536x1536'], $sizes['2048x2048']);
    return $sizes;
}

function herizon_cars_big_image_threshold(): int
{
    return 2560;
}

function herizon_cars_remove_self_pingbacks(array &$links): void
{
    $home_host = wp_parse_url(home_url('/'), PHP_URL_HOST);
    if (! is_string($home_host) || $home_host === '') {
        return;
    }
    foreach ($links as $key => $link) {
        $host = wp_parse_url($link, PHP_URL_HOST);
        if (is_string($host) && strcasecmp($host, $home_host) === 0) {
            unset($links[$key]);
        }
    }
}

function herizon_cars_slug_body_class(array $classes): array
{
    $post = get_queried_object();
    if (is_singular() && $post instanceof WP_Post && $post->post_name !== '') {
        $classes[] = sanitize_html_class($post->post_type . '-' . $post->post_name);
    }
    return array_values(array_unique($classes));
}

function herizon_cars_year_shortcode(): string
{
    return esc_html(wp_date('Y'));
}
