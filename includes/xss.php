<?php
/** Encode at the output boundary, according to the browser parsing context. */
function e($value): string
{
    if (!is_scalar($value) && $value !== null && !($value instanceof Stringable)) {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/** A complete JSON literal for use inside a trusted script, without extra quotes. */
function js_value($value): string
{
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
}

/** Raw validated URL. Escape the result with e() when rendering an attribute. */
function safe_url($value, string $fallback = '#'): string
{
    if (!is_string($value) || $value === '' || strlen($value) > 8192) {
        return $fallback;
    }
    $url = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $check = $url;
    // Inspect encoded controls/schemes too; never rely on escaping alone for URLs.
    for ($i = 0; $i < 3; $i++) {
        $decoded = rawurldecode(html_entity_decode($check, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($decoded === $check) {
            break;
        }
        $check = $decoded;
    }
    if ($url === '' || preg_match('/[\x00-\x1f\x7f\\\\]/u', $check) !== 0
        || strpos($check, '//') === 0) {
        return $fallback;
    }
    $scheme = parse_url($check, PHP_URL_SCHEME);
    if ($scheme === false || ($scheme !== null && !in_array(strtolower($scheme), ['http', 'https'], true))) {
        return $fallback;
    }
    // Spaces in legitimate filenames/query values must become URL data.
    $url = str_replace(' ', '%20', $url);
    if ($scheme !== null) {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || !filter_var($url, FILTER_VALIDATE_URL)) {
            return $fallback;
        }
    } elseif (preg_match('/\A[^\/?#]*:/', $check)) {
        return $fallback;
    }
    return $url;
}

/** Sanitize rich HTML using the pinned upstream HTML Purifier implementation. */
function clean_html($html): string
{
    static $purifier;
    if (!is_string($html) || $html === '') {
        return '';
    }
    if ($purifier === null) {
        require_once dirname(__DIR__) . '/lib/htmlpurifier/library/HTMLPurifier.auto.php';
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', 'p[style],br,strong,b,em,i,u,s,ul,ol,li,blockquote,pre,code,h2,h3,h4,'
            . 'span[style],div[style],a[href|title],table,thead,tbody,tr,th[colspan|rowspan],td[colspan|rowspan],'
            . 'img[src|alt|title|width|height]');
        $config->set('CSS.AllowedProperties', ['color', 'background-color', 'font-size', 'font-weight',
            'font-style', 'text-align', 'text-decoration']);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('HTML.ForbiddenAttributes', ['id', 'name']);
        $config->set('HTML.SafeIframe', false);
        $config->set('HTML.SafeObject', false);
        // No writable source/cache directory required in the hardened Docker image.
        $config->set('Cache.DefinitionImpl', null);
        $purifier = new HTMLPurifier($config);
    }
    return $purifier->purify($html);
}

function csp_nonce(): string
{
    static $nonce;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(32));
    }
    return $nonce;
}
