<?php
/** Standalone release/updater contracts. Run: php -n tests/php/github-updates.php */
error_reporting(E_ALL);
set_error_handler(static function ($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});
define('ABSPATH', __DIR__ . '/');
define('HOUR_IN_SECONDS', 3600);
define('MINUTE_IN_SECONDS', 60);
$GLOBALS['hc_update_hooks'] = [];
$GLOBALS['hc_update_requests'] = [];
$GLOBALS['hc_update_cache'] = [];
function add_filter($hook, $callback, $priority = 10, $args = 1) { $GLOBALS['hc_update_hooks'][] = [$hook, $callback, $priority, $args]; }
function get_site_transient($key) { return $GLOBALS['hc_update_cache'][$key]['value'] ?? false; }
function set_site_transient($key, $value, $ttl) { $GLOBALS['hc_update_cache'][$key] = ['value' => $value, 'ttl' => $ttl]; }
function wp_safe_remote_get($url, $args) { $GLOBALS['hc_update_requests'][] = [$url, $args]; return $GLOBALS['hc_update_response']; }
class WP_Error {}
function is_wp_error($value) { return $value instanceof WP_Error; }
function wp_remote_retrieve_response_code($response) { return $response['response']['code'] ?? ''; }
function wp_remote_retrieve_body($response) { return $response['body'] ?? ''; }
require dirname(__DIR__, 2) . '/inc/integrations/github-updates.php';
$count = 0;
function expect_update($condition, $message) { global $count; ++$count; if (!$condition) { throw new RuntimeException($message); } }
function release_fixture($version = '1.2.3') {
    return [
        'tag_name' => 'v' . $version,
        'draft' => false,
        'prerelease' => false,
        'html_url' => 'https://github.com/abel-hechavarria/herizon-cars/releases/tag/v' . $version,
        'body' => "Release notes\n<!-- herizon-cars-update {\"requires\":\"6.5\",\"requires_php\":\"8.2\"} -->\n",
        'assets' => [[
            'name' => 'herizon-cars-' . $version . '.zip',
            'state' => 'uploaded',
            'size' => 42000,
            'browser_download_url' => 'https://github.com/abel-hechavarria/herizon-cars/releases/download/v' . $version . '/herizon-cars-' . $version . '.zip',
        ]],
    ];
}
function update_response($response) {
    $GLOBALS['hc_update_cache'] = [];
    $GLOBALS['hc_update_response'] = $response;
    return herizon_cars_github_theme_update(false, ['UpdateURI' => 'https://github.com/abel-hechavarria/herizon-cars'], 'herizon-cars', ['en_US']);
}
function release_response($release) { return ['response' => ['code' => 200], 'body' => json_encode($release)]; }
expect_update($GLOBALS['hc_update_hooks'] === [['update_themes_github.com', 'herizon_cars_github_theme_update', 10, 4]], 'Native hostname filter accepts all four arguments.');
$fixture = release_fixture();
$expected = ['theme' => 'herizon-cars', 'version' => '1.2.3', 'url' => $fixture['html_url'], 'package' => $fixture['assets'][0]['browser_download_url'], 'requires' => '6.5', 'requires_php' => '8.2'];
expect_update(herizon_cars_github_release_update($fixture) === $expected, 'Exact stable uploaded release produces native fields.');
$actual = herizon_cars_github_release_update($fixture);
expect_update(!array_key_exists('autoupdate', $actual) && !array_key_exists('tested', $actual), 'No auto-update override or unverified tested claim.');
expect_update(herizon_cars_github_release_update(release_fixture('2.0.0'))['version'] === '2.0.0', 'Future stable major supported with explicit requirements.');
$future = $fixture;
$future['body'] = '<!-- herizon-cars-update {"requires_php":"8.3.1","requires":"6.8.2"} -->';
expect_update(herizon_cars_github_release_update($future)['requires_php'] === '8.3.1', 'Future requirements and reversed property order respected.');
foreach (['1.2.3', 'v1.2', 'v01.2.3', 'v1.02.3', 'v1.2.03', 'v1.2.3-rc1', 'v1.2.3+build', "v1.2.3\n", 'v1.2.3/path', [], null] as $tag) {
    $release = $fixture; $release['tag_name'] = $tag;
    expect_update(herizon_cars_github_release_update($release) === false, 'Malformed/non-stable tag rejected.');
}
foreach (['draft', 'prerelease'] as $flag) {
    foreach ([true, 'false', 0, null] as $value) {
        $release = $fixture; $release[$flag] = $value;
        expect_update(herizon_cars_github_release_update($release) === false, 'Release flags must explicitly be false.');
    }
}
foreach (['', 'https://github.com/other/herizon-cars/releases/tag/v1.2.3', 'http://github.com/abel-hechavarria/herizon-cars/releases/tag/v1.2.3', 'https://github.com.evil.test/abel-hechavarria/herizon-cars/releases/tag/v1.2.3', $fixture['html_url'] . '?redirect=evil'] as $url) {
    $release = $fixture; $release['html_url'] = $url;
    expect_update(herizon_cars_github_release_update($release) === false, 'Release URL pinned to exact repository/tag.');
}
foreach (['', 'Release prose only', '<!-- herizon-cars-update {broken} -->', '<!-- herizon-cars-update {"requires":"6.5"} -->', '<!-- herizon-cars-update {"requires":"6.5","requires_php":"8.2","url":"https://evil.test"} -->', '<!-- herizon-cars-update {"requires":6.5,"requires_php":"8.2"} -->', '<!-- herizon-cars-update {"requires":"6.5-beta","requires_php":"8.2"} -->', '<!-- herizon-cars-update {"requires":"6.5","requires_php":"https://evil.test"} -->', $fixture['body'] . $fixture['body'], null] as $body) {
    $release = $fixture; $release['body'] = $body;
    expect_update(herizon_cars_github_release_update($release) === false, 'Missing/malformed/duplicate or unsafe requirements marker rejected.');
}
foreach (['name' => ['herizon-cars.zip', 'herizon-cars-1.2.4.zip'], 'state' => ['new', null], 'size' => [0, -1, '42000', null], 'browser_download_url' => [
    'https://evil.test/herizon-cars-1.2.3.zip',
    'https://github.com/other/herizon-cars/releases/download/v1.2.3/herizon-cars-1.2.3.zip',
    'http://github.com/abel-hechavarria/herizon-cars/releases/download/v1.2.3/herizon-cars-1.2.3.zip',
    'https://github.com/abel-hechavarria/herizon-cars/archive/refs/tags/v1.2.3.zip',
    $fixture['assets'][0]['browser_download_url'] . '?redirect=evil',
]] as $key => $values) {
    foreach ($values as $value) {
        $release = $fixture; $release['assets'][0][$key] = $value;
        expect_update(herizon_cars_github_release_update($release) === false, 'Invalid asset rejected: ' . $key);
    }
}
foreach ([[], null, ['bad asset']] as $assets) {
    $release = $fixture; $release['assets'] = $assets;
    expect_update(herizon_cars_github_release_update($release) === false, 'Missing or malformed asset rejected.');
}
$release = $fixture; array_unshift($release['assets'], ['name' => 'unrelated.txt']);
expect_update(herizon_cars_github_release_update($release) === $expected, 'Unrelated release assets are harmless.');
$GLOBALS['hc_update_response'] = release_response($fixture);
$before = count($GLOBALS['hc_update_requests']);
foreach ([['other-theme', 'https://github.com/abel-hechavarria/herizon-cars'], ['herizon-cars', 'https://github.com/other/repo'], ['herizon-cars', '']] as [$slug, $uri]) {
    expect_update(herizon_cars_github_theme_update(false, ['UpdateURI' => $uri], $slug, []) === false, 'Unrelated theme/header unchanged.');
}
$prior = ['version' => '9.0.0', 'package' => 'https://other-provider.test/theme.zip'];
expect_update(herizon_cars_github_theme_update($prior, ['UpdateURI' => 'https://github.com/abel-hechavarria/herizon-cars'], 'herizon-cars', []) === $prior, 'Existing provider preserved.');
expect_update(count($GLOBALS['hc_update_requests']) === $before, 'Unrelated/preexisting updates never request GitHub.');
expect_update(update_response(release_response($fixture)) === $expected, 'Valid HTTP response yields update.');
$cache = $GLOBALS['hc_update_cache']['herizon_cars_github_release_v1'];
expect_update($cache['ttl'] === 3600, 'Successful metadata cached for one hour.');
$before = count($GLOBALS['hc_update_requests']);
expect_update(herizon_cars_github_theme_update(false, ['UpdateURI' => 'https://github.com/abel-hechavarria/herizon-cars'], 'herizon-cars', []) === $expected, 'Success cache reused.');
expect_update(count($GLOBALS['hc_update_requests']) === $before, 'Cached success avoids HTTP.');
[$url, $args] = end($GLOBALS['hc_update_requests']);
expect_update($url === 'https://api.github.com/repos/abel-hechavarria/herizon-cars/releases/latest', 'Fixed public API endpoint.');
expect_update($args['timeout'] === 8 && $args['redirection'] === 0 && $args['limit_response_size'] === 262144, 'Finite timeout/body and no API redirects.');
expect_update(!isset($args['headers']['Authorization']), 'No GitHub token.');
foreach ([new WP_Error(), ['response' => ['code' => 404]], ['response' => ['code' => 403]], ['response' => ['code' => 429]], ['response' => ['code' => 500]], ['response' => ['code' => 302]], ['response' => ['code' => 200], 'body' => '{broken'], ['response' => ['code' => 200], 'body' => 'null'], ['response' => ['code' => 200], 'body' => '[]'], release_response(['tag_name' => 'v1.2.3'])] as $response) {
    expect_update(update_response($response) === false, 'HTTP/network/JSON/validation failure fails closed.');
    expect_update($GLOBALS['hc_update_cache']['herizon_cars_github_release_v1']['ttl'] === 300, 'Failure cached for five minutes.');
    $before = count($GLOBALS['hc_update_requests']);
    expect_update(herizon_cars_github_theme_update(false, ['UpdateURI' => 'https://github.com/abel-hechavarria/herizon-cars'], 'herizon-cars', []) === false, 'Failure cache returns no update.');
    expect_update(count($GLOBALS['hc_update_requests']) === $before, 'Cached failure avoids HTTP.');
}
echo "GitHub updates: {$count} assertions PASS\n";
