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


defined('MOODLE_INTERNAL') || exit();
defined('SECRET') || define('SECRET', 'ThisIsASecretPasswordChangeMe');

require_once $CFG->libdir.'/filelib.php';

class filter_pumukitmedia extends moodle_text_filter
{
    public const PLAYLIST_SEARCH_REGEX = '/<iframe[^>]*?src=\"(https:\\/\\/[^>]*?\\/openedx\\/openedx\\/playlist\\/embed.*?)".*?>.*?<\\/iframe>/is';
    public const VIDEO_SEARCH_REGEX = '/<iframe[^>]*?src=\"(https:\\/\\/[^>]*?\\/openedx\\/openedx\\/embed.*?)".*?>.*?<\\/iframe>/is';
    public const LEGACY_VIDEO_SEARCH_REGEX = '/<a\\s[^>]*href=["\'](https?:\\/\\/[^>]*?\\/openedx\\/openedx\\/embed.*?)["\']>.*?<\\/a>/is';
    public const LEGACY_PLAYLIST_SEARCH_REGEX = '/<a\\s[^>]*href=["\'](https?:\\/\\/[^>]*?\\/openedx\\/openedx\\/playlist\\/embed.*?)["\']>.*?<\\/a>/is';

    public function filter($text, array $options = []): string
    {
        // If the text does not contain any link or iframe, return the text as is.
        if (!filter_is_valid_text($text)) {
            return $text;
        }

        // Check if the text is a legacy url and convert it to the new format.
        if (filter_is_legacy_url($text)) {
            $parsedUrl = filter_convert_legacy_url($text);
            $search = (filter_is_a_playlist($parsedUrl)) ? self::LEGACY_PLAYLIST_SEARCH_REGEX : self::LEGACY_VIDEO_SEARCH_REGEX;
            $iframe = preg_replace_callback($search, 'filter_pumukitmedia_callback', $parsedUrl);
            if (filter_validate_returned_iframe($text, $iframe)) {
                return $iframe;
            }
        }

        // Check if the text is an iframe and convert it to the new format.
        if (filter_is_an_iframe($text)) {
            $search = (filter_is_a_playlist($text)) ? self::PLAYLIST_SEARCH_REGEX : self::VIDEO_SEARCH_REGEX;
            $iframe = preg_replace_callback($search, 'filter_pumukitmedia_openedx_callback', $text);
            if (filter_validate_returned_iframe($text, $iframe)) {
                return $iframe;
            }
        }

        return $text;
    }
}

function filter_replace_id_param(string $text): string
{
    $stringReplace = get_id_param($text);
    if($stringReplace === null) {
        return $text;
    }

    return str_replace($stringReplace, '', $text);
}

function get_id_param(string $text): ?string
{
    if(false !== strpos('?id=', $text)) {
        return '?id=';
    }

    if(false !== strpos('/?id=', $text)) {
        return '/?id=';
    }

    return null;
}

function filter_convert_legacy_url(string $text): string
{
    if (false !== stripos($text, 'playlist')) {
        return str_replace('pumoodle/embed/playlist', 'openedx/openedx/playlist/embed', $text);
    }

    return str_replace('pumoodle/embed', 'openedx/openedx/embed', $text);
}


function filter_validate_returned_iframe(string $oldText, string $newText): bool
{
    return !empty($newText) && $newText !== $oldText;
}

function filter_is_a_playlist(string $text): bool
{
    return false !== stripos($text, 'playlist');
}

function filter_is_an_iframe(string $text): bool
{
    return false !== stripos($text, '<iframe');
}

function filter_is_an_link(string $text): bool
{
    return false !== stripos($text, '<a');
}

function filter_is_legacy_url(string $text): bool
{
    return false !== stripos($text, 'pumoodle/');
}

function filter_is_valid_text(string $text): bool
{
    $isValidText = false;
    if (!empty($text)) {
        $isValidText = true;
    }

    if (filter_is_an_link($text) && filter_is_an_iframe($text)) {
        $isValidText = true;
    }

    return $isValidText;
}

function filter_pumukitmedia_openedx_callback(array $link): string
{
    $link_params = [];
    parse_str(html_entity_decode(parse_url($link[1], PHP_URL_QUERY)), $link_params);

    $hasIdParam = get_id_param($link[1]);
    if($hasIdParam === null) {
        $urlElements = explode('/', $link[1]);
        $mm_id = end($urlElements);
        $url = generateURL($link_params, $mm_id, $link[1]);

        return str_replace($link[1], $url, $link[0]);
    }

    $mm_id = $link_params['id'] ?? null;
    if (!$mm_id) {
        $mm_id = $link_params['playlist'] ?? null;
    }

    $url = generateURL($link_params, $mm_id, $link[1]);

    return str_replace($link[1], $url, $link[0]);
}


function filter_pumukitmedia_callback(array $link): string
{
    global $CFG;

    $link_params = [];
    parse_str(html_entity_decode(parse_url($link[1], PHP_URL_QUERY)), $link_params);

    $hasIdParam = get_id_param($link[1]);
    if($hasIdParam === null) {
        $urlElements = explode('/', $link[1]);
        $mm_id = end($urlElements);
        $url = generateURL($link_params, $mm_id, $link[1]);

        return str_replace($link[1], $url, $link[0]);
    }

    $multiStream = isset($link_params['multistream']) && '1' == $link_params['multistream'];
    $mm_id = $link_params['id'] ?? null;

    $url = generateURL($link_params, $mm_id, $link[1]);
    //Prepare and return iframe with correct sizes to embed on webpage.
    if ($multiStream) {
        $iframe_width = $CFG->iframe_multivideo_width ?: '100%';
        $iframe_height = $CFG->iframe_multivideo_height ?: '333px';
    } else {
        $iframe_width = $CFG->iframe_singlevideo_width ?: '592px';
        $iframe_height = $CFG->iframe_singlevideo_height ?: '333px';
    }
    return '<iframe src="'.$url.'"'.
        '        style="border:0px #FFFFFF none; width:'.$iframe_width.'; height:'.$iframe_height.';"'.
        '        scrolling="no" frameborder="0" webkitallowfullscreen="true" mozallowfullscreen="true" allowfullscreen="true" >'.
        '</iframe>';
}

function generateURL(array $link_params, string $mm_id, string $url1): string
{
    $email = $link_params['email'] ?? null;
    $extra_arguments = [
        'professor_email' => $email,
        'hash' => filter_create_ticket($mm_id, $email ?: '', parse_url($url1, PHP_URL_HOST)),
    ];

    return $url1.'?'.http_build_query(array_merge($extra_arguments, $link_params));
}

function filter_create_ticket(string $id, string $email, string $domain): string
{
    global $CFG;

    $secret = empty($CFG->filter_pumukitmedia_secret) ? SECRET : $CFG->filter_pumukitmedia_secret;

    $date = date('d/m/Y');

    return md5($email.$secret.$date.$domain);
}
