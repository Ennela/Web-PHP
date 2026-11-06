<?php
/** HTTPS transport policy shared by outgoing API calls. No insecure fallback. */
function app_https_curl_options(): array
{
    $options = [
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS => 0,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
        CURLOPT_PROXY_SSL_VERIFYPEER => true,
        CURLOPT_PROXY_SSL_VERIFYHOST => 2,
        CURLOPT_PROXY_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
    ];
    // The string protocol options need PHP 8.3/libcurl 7.85. Keep PHP 8.1/8.2
    // installations secure too, without using deprecated constants on newer PHP.
    if (defined('CURLOPT_PROTOCOLS_STR') && defined('CURLOPT_REDIR_PROTOCOLS_STR')) {
        $options[CURLOPT_PROTOCOLS_STR] = 'https';
        $options[CURLOPT_REDIR_PROTOCOLS_STR] = 'https';
    } else {
        $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
        $options[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTPS;
    }
    return $options;
}
