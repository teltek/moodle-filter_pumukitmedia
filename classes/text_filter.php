<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * pumukitmedia link filtering
 *
 * This filter will replace any link generated with atto_pumukitmedia repository
 * with an iframe that will retrieve the content served by atto_pumukitmedia.
 *
 * It uses ideas from the media plugin filter and the helloworld filter template.
 *
 * @package    filter_pumukitmedia
 * @copyright  Teltek Video Research
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_pumukitmedia;

defined('MOODLE_INTERNAL') || die();
defined('SECRET') || define('SECRET', 'ThisIsASecretPasswordChangeMe');

require_once($GLOBALS['CFG']->libdir . '/filelib.php');

class text_filter extends \core_filters\text_filter
{
    public const PLAYLIST_SEARCH_REGEX = '/<iframe[^>]*?src=\"(https:\\/\\/[^>]*?\\/openedx\\/openedx\\/playlist\\/embed.*?)".*?>.*?<\\/iframe>/is';
    public const VIDEO_SEARCH_REGEX = '/<iframe[^>]*?src=\"(https:\\/\\/[^>]*?\\/openedx\\/openedx\\/embed.*?)".*?>.*?<\\/iframe>/is';
    public const LEGACY_VIDEO_SEARCH_REGEX = '/<a\\s[^>]*href=["\'](https?:\\/\\/[^>]*?\\/openedx\\/openedx\\/embed.*?)["\']>.*?<\\/a>/is';
    public const LEGACY_PLAYLIST_SEARCH_REGEX = '/<a\\s[^>]*href=["\'](https?:\\/\\/[^>]*?\\/openedx\\/openedx\\/playlist\\/embed.*?)["\']>.*?<\\/a>/is';
    public const MEDIA_LINK_REGEX = '/<a[^>]+href="([^"]*)"(?:[^>]*\bclass="[^"]*\bpumukit-media-link\b[^"]*")?[^>]*>.*?<\/a>/i';

    public function filter($text, array $options = [])
    {
        if (!filter_is_valid_text($text)) {
            return $text;
        }

        if (filter_is_an_media_link($text)) {
            $iframe = preg_replace_callback(self::MEDIA_LINK_REGEX, '\\filter_pumukitmedia\\filter_media_link_callback', $text);
            if (filter_validate_returned_iframe($text, $iframe)) {
                return $iframe;
            }
        }

        if (filter_is_legacy_url($text)) {
            $parsedUrl = filter_convert_legacy_url($text);
            $search = (filter_is_a_playlist($parsedUrl)) ? self::LEGACY_PLAYLIST_SEARCH_REGEX : self::LEGACY_VIDEO_SEARCH_REGEX;
            $iframe = preg_replace_callback($search, '\\filter_pumukitmedia\\filter_pumukitmedia_callback', $parsedUrl);
            if (filter_validate_returned_iframe($text, $iframe)) {
                return $iframe;
            }
        }

        if (filter_is_an_iframe($text)) {
            $search = (filter_is_a_playlist($text)) ? self::PLAYLIST_SEARCH_REGEX : self::VIDEO_SEARCH_REGEX;
            $iframe = preg_replace_callback($search, '\\filter_pumukitmedia\\filter_pumukitmedia_openedx_callback', $text);
            if (filter_validate_returned_iframe($text, $iframe)) {
                return $iframe;
            }
        }

        return $text;
    }
}

// --- Funciones auxiliares fuera de la clase --- //

function get_id_param(string $text): ?string
{
    if (strpos($text, '?id=') !== false) return '?id=';
    if (strpos($text, '/?id=') !== false) return '/?id=';
    return null;
}

function filter_convert_legacy_url(string $text): string
{
    if (stripos($text, 'playlist') !== false) {
        return str_replace('pumoodle/embed/playlist', 'openedx/openedx/playlist/embed', $text);
    }
    return str_replace('pumoodle/embed', 'openedx/openedx/embed', $text);
}

function filter_validate_returned_iframe(string $oldText, string $newText): bool
{
    return !empty($newText) && $newText !== $oldText;
}

function filter_is_an_media_link(string $text): bool
{
    return stripos($text, 'pumukit-media-link') !== false;
}

function filter_is_a_playlist(string $text): bool
{
    return stripos($text, 'playlist') !== false;
}

function filter_is_an_iframe(string $text): bool
{
    return stripos($text, '<iframe') !== false;
}

function filter_is_an_link(string $text): bool
{
    return stripos($text, '<a') !== false;
}

function filter_is_legacy_url(string $text): bool
{
    return stripos($text, 'pumoodle/') !== false;
}

function filter_is_valid_text(string $text): bool
{
    return !empty($text) || (filter_is_an_link($text) && filter_is_an_iframe($text));
}

function filter_pumukitmedia_openedx_callback(array $link): string
{
    $link_params = [];
    parse_str(html_entity_decode((string) parse_url($link[1], PHP_URL_QUERY)), $link_params);

    $hasIdParam = get_id_param($link[1]);
    $mm_id = $link_params['id'] ?? $link_params['playlist'] ?? null;

    if ($hasIdParam === null && !$mm_id) {
        $urlElements = explode('/', $link[1]);
        $mm_id = end($urlElements);
    }

    $url = generateURL($link_params, $mm_id, $link[1]);

    return str_replace($link[1], $url, $link[0]);
}

function filter_pumukitmedia_callback(array $link): string
{
    $link_params = [];
    parse_str(html_entity_decode((string) parse_url($link[1], PHP_URL_QUERY)), $link_params);

    $hasIdParam = get_id_param($link[1]);
    $mm_id = $link_params['id'] ?? null;

    if ($hasIdParam === null && !$mm_id) {
        $urlElements = explode('/', $link[1]);
        $mm_id = end($urlElements);
    }

    $url = generateURL($link_params, $mm_id, $link[1]);

    $isMultiStream = isset($link_params['multistream']) && $link_params['multistream'] == '1';

    return generate_iframe($url, $isMultiStream);
}

function filter_media_link_callback(array $link): string
{
    $url = $link[1];
    $link_params = [];
    parse_str(html_entity_decode((string) parse_url($url, PHP_URL_QUERY)), $link_params);


    $regexParam = get_id_param($url);
    $id = $link_params['id'] ?? null;

    if ($regexParam !== null) {
        $parts = explode($regexParam, $url);
        $id = $parts[1] ?? null;
        $url = ($regexParam === '/?id=') ? ($parts[0] . '/' . $id) : ($parts[0] . $id);
    }

    if (!$id) {
        $segments = explode('/', $url);
        $id = end($segments);
    }

    $url = generateURL($link_params, $id, $url);

    return generate_iframe($url, false);
}

function generateURL(array $link_params, string $mm_id, string $baseUrl): string
{
    $email = $link_params['email'] ?? '';
    $domain = parse_url($baseUrl, PHP_URL_HOST);
    $extra = [
        'professor_email' => $email,
        'hash' => filter_create_ticket($mm_id, $email, $domain),
    ];

    $merged = array_merge($link_params, $extra);
    $base = strtok($baseUrl, '?');
    return $base . '?' . http_build_query($merged);
}

function filter_create_ticket(string $id, string $email, string $domain): string
{
    global $CFG;
    $secret = $CFG->filter_pumukitmedia_secret ?? SECRET;
    return md5($email . $secret . date('d/m/Y') . $domain);
}

function generate_iframe(string $url, bool $isMultiStream): string
{
    global $CFG;

    $width = $isMultiStream ? ($CFG->iframe_multivideo_width ?? '100%') : ($CFG->iframe_singlevideo_width ?? '592px');
    $height = $isMultiStream ? ($CFG->iframe_multivideo_height ?? '333px') : ($CFG->iframe_singlevideo_height ?? '333px');

    return '<div class="embed-responsive embed-responsive-16by9 tv-iframe">' .
        '<iframe class="embed-responsive-item tv-iframe-item" src="' . $url . '"' .
        ' style="border:0; width:' . $width . '; height:' . $height . '; overflow:hidden"' .
        ' allow="fullscreen"></iframe></div>';
}
