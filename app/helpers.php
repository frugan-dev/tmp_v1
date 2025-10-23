<?php

declare(strict_types=1);

/*
 * This file is part of the Sage theme.
 *
 * (ɔ) Frugan <dev@frugan.it>
 *
 * This source file is subject to the GNU GPLv3 license that is bundled
 * with this source code in the file COPYING.
 */

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\ShortNumberInfo;
use Propaganistas\LaravelPhone\PhoneNumber;

if (! function_exists('getClientIp')) {
    // https://adam-p.ca/blog/2022/03/x-forwarded-for/
    // https://developers.cloudflare.com/support/troubleshooting/restoring-visitor-ips/restoring-original-visitor-ips/
    // https://developers.cloudflare.com/fundamentals/reference/http-request-headers/
    // https://snicco.io/blog/how-to-safely-get-the-ip-address-in-a-wordpress-plugin
    // https://snicco.io/vulnerability-disclosure/wordfence/dos-through-ip-spoofing-wordfence-7-6-2
    // https://stackoverflow.com/a/2031935/3929620
    // https://stackoverflow.com/a/58239702/3929620
    function getClientIp()
    {
        $ip = request()->ip();

        foreach ([
            'REMOTE_ADDR', // The only truly reliable one if there are no proxies

            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP', // Traefik, Nginx
            'HTTP_TRUE_CLIENT_IP', // Cloudflare, Akamai

            // Less reliable headers, easily spoofed
            'HTTP_X_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_CLIENT_IP',
            'HTTP_X_CLUSTER_CLIENT_IP',
        ] as $key) {
            if (! array_key_exists($key, $_SERVER)) {
                continue;
            }

            // For headers with IP lists, we take the first non-private IP from the beginning
            // (X-Forwarded-For is in order client -> proxy1 -> proxy2)
            $ips = array_map('trim', explode(',', (string) $_SERVER[$key]));

            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $ip;
    }
}

if (! function_exists('escJs')) {
    function escJs(string $raw)
    {
        // pre-empt replacement
        if ($raw === '' || ctype_digit((string) $raw)) {
            return $raw;
        }

        // do we have a valid UTF-8 string?
        if (! \Safe\preg_match('/^./su', $raw)) {
            $message = sprintf("'%s' in an invalid UTF-8 string", $raw);

            throw new \Exception($message);
        }

        // escape the string in UTF-8 encoding
        return preg_replace_callback(
            '/[^a-z0-9,\._]/iSu',
            function ($matches) {
                // get the character
                $chr = $matches[0];

                // is it UTF-8?
                if (strlen($chr) == 1) {
                    // yes
                    return sprintf('\\x%02X', ord($chr));
                }
                // no
                if (function_exists('iconv')) {
                    $chr = (string) \Safe\iconv('UTF-8', 'UTF-16BE', $chr);
                } elseif (function_exists('mb_convert_encoding')) {
                    $chr = (string) \Safe\mb_convert_encoding($chr, 'UTF-16BE', 'UTF-8');
                } else {
                    $message = "Extension 'iconv' or 'mbstring' is required.";

                    throw new \Exception($message);
                }

                return sprintf('\\u%04s', strtoupper(bin2hex($chr)));
            },
            $raw
        );
    }
}

if (! function_exists('escAttr')) {
    function escAttr(mixed $raw)
    {
        if (! is_array($raw)) {
            // pre-empt replacement
            if ($raw === '' || ctype_digit((string) $raw)) {
                return $raw;
            }

            if (filter_var($raw, FILTER_VALIDATE_URL)) {
                return $raw;
            }

            return htmlspecialchars(
                (string) $raw,
                ENT_COMPAT,
                'UTF-8',
                false
            );
        }

        $esc = ' ';
        foreach ($raw as $key => $val) {
            // do not add null and false values
            if ($val === null || $val === false) {
                continue;
            }

            // get rid of extra spaces in the key
            $key = trim($key);

            // concatenate and space-separate multiple values
            if (is_array($val)) {
                $val = implode(' ', $val);
            }

            // what kind of attribute representation?
            if ($val === true) {
                // minimized
                $esc .= escAttr($key);
            } else {
                // full; because it is quoted, we can use html escaping
                $esc .= escAttr($key).'="';

                // pre-empt escaping
                if ($val === '' || ctype_digit((string) $val)) {
                    $esc .= $val;
                } elseif (filter_var($val, FILTER_VALIDATE_URL)) {
                    $esc .= $val;
                } else {
                    $esc .= htmlspecialchars(
                        (string) $val,
                        ENT_COMPAT,
                        'UTF-8',
                        false
                    );
                }

                $esc .= '"';
            }

            // space separator
            $esc .= ' ';
        }

        // done; remove the last space
        return rtrim($esc);
    }
}

// https://gist.github.com/grexlort/00cd35c9e6f6e5d2c6f2?permalink_comment_id=3734962#gistcomment-3734962
// https://github.com/umpirsky/country-list/issues/130
if (! function_exists('getPhoneCountry')) {
    function getPhoneCountry()
    {
        app()->singletonIf('PhoneNumberUtil', fn () => PhoneNumberUtil::getInstance());
        app()->singletonIf('ShortNumberInfo', fn () => ShortNumberInfo::getInstance());
        app()->singletonIf('countryData', fn () => file_exists($file = base_path().'/vendor/umpirsky/country-list/data/'.app()->getLocale().'/country.php') ? require $file : []);

        return array_map(fn ($region) => [
            'region' => $region,
            'name' => resolve('countryData')[$region],
            'code' => resolve('PhoneNumberUtil')->getCountryCodeForRegion($region),
        ], collect(resolve('ShortNumberInfo')->getSupportedRegions())->filter(fn ($region) => Arr::exists(resolve('countryData'), $region))->toArray());
    }
}

// https://github.com/funkjedi/composer-include-files
if (! function_exists('phone')) {
    function phone(?string $number, $country = [], $format = null)
    {
        try {
            $phone = new PhoneNumber($number, $country);

            if (! is_null($format)) {
                return $phone->format($format);
            }

            return $phone;
        } catch (Exception) {
        }

        return $number;
    }
}

// FIXME - @stack rendered before @include
// This is intentional. The @stack is rendered before the @include executes to @push, before anything is added to it.
// https://github.com/laravel/ideas/issues/864
// https://github.com/laravel/framework/issues/13998
// https://laravel.io/forum/04-21-2017-laravel-not-rendering-stack
// https://alishoff.com/blog/387
if (! function_exists('pushData')) {
    function pushData(string $name, int $weight = 0): void
    {
        if (! app()->bound('data')) {
            app()->instance('data', []);
        }

        $sections = app()->view->getSections();
        $content = array_pop($sections);

        $arr = app('data');
        $arr[$name][] = [
            'content' => $content,
            'weight' => $weight,
        ];

        app()->instance('data', $arr);
    }
}

if (! function_exists('mergeData')) {
    function mergeData(array $data): void
    {
        if (! app()->bound('data')) {
            app()->instance('data', $data);
        } else {
            app()->instance('data', collect(app('data'))->mergeRecursive($data)->toArray());
        }
    }
}

if (! function_exists('getAcfEmails')) {
    function getAcfEmails($field_name)
    {
        if (! empty($value = get_field($field_name, 'option'))) {
            return explode("\n", $value);
        }

        return explode(',', config('mail.fallback.to.address'));
    }
}

if (! function_exists('updateAcfEmails')) {
    function updateAcfEmails($value, $post_id, $field)
    {
        if (empty($value)) {
            return $value;
        }

        $value = trim($value);

        $emails = explode("\n", $value);
        $emails = array_map(fn ($email) => Str::of($email)->trim()->lower(), $emails);
        $emails = array_values(array_filter($emails));

        return implode("\n", $emails);
    }
}

if (! function_exists('validateAcfEmails')) {
    function validateAcfEmails($valid, $value, $field, $input)
    {
        if (! $valid) {
            return $valid;
        }

        $value = trim($value);

        if (empty($value) && ! empty($field['required'])) {
            return __('One or more email addresses are invalid.', 'sage');
        }

        if (! empty($value)) {
            $emails = explode("\n", $value);
            $emails = array_map('trim', $emails);

            $validator = Validator::make(
                ['emails' => $emails],
                ['emails.*' => 'required|email:rfc,strict,dns,spoof']
            );

            if ($validator->fails()) {
                return __('One or more email addresses are invalid.', 'sage');
            }
        }

        return $valid;
    }
}

if (! function_exists('categoryList')) {
    function categoryList(int|bool|null $post = null, int $limit = 0)
    {
        if (empty($post)) {
            $post = get_the_ID();
        }

        switch (get_post_type($post)) {
            case 'post':
                // This function only returns results from the default "category" taxonomy.
                // For custom taxonomies use get_the_terms().
                if (! empty($categories = get_the_category($post))) {
                    if ($limit > 0 && count($categories) > $limit) {
                        $categories = array_slice($categories, 0, $limit);

                        $links = [];
                        foreach ($categories as $category) {
                            $links[] = sprintf(
                                '<a href="%s">%s</a>',
                                esc_url(get_category_link($category->term_id)),
                                esc_html(strLimit($category->name, 5))
                            );
                        }

                        return implode(', ', $links).'...';
                    } else {
                        return get_the_category_list(separator: ', ', post_id: $post);
                    }
                }
                break;
        }
    }
}

if (! function_exists('tagList')) {
    function tagList(int|bool|null $post = null, int $limit = 0)
    {
        if (empty($post)) {
            $post = get_the_ID();
        }

        switch (get_post_type($post)) {
            case 'post':
                if (! empty($tags = get_the_tags($post))) {
                    if ($limit > 0 && count($tags) > $limit) {
                        $tags = array_slice($tags, 0, $limit);

                        $links = [];
                        foreach ($tags as $tag) {
                            $links[] = sprintf(
                                '<a href="%s">%s</a>',
                                esc_url(get_tag_link($tag->term_id)),
                                esc_html(strLimit($tag->name, 5))
                            );
                        }

                        return implode(', ', $links).'...';
                    } else {
                        return get_the_tag_list(sep: ', ', post_id: $post);
                    }
                }
                break;
        }
    }
}

if (! function_exists('excerpt')) {
    function excerpt(int|bool|null $post = null, ?callable $length_callback = null, ?callable $more_callback = null): string
    {
        if (empty($post)) {
            $post = get_the_ID();
        }

        if ($length_callback !== null) {
            add_filter('excerpt_length', $length_callback);
        }

        if ($more_callback !== null) {
            add_filter('excerpt_more', $more_callback);
        }

        $output = get_the_excerpt($post);
        // $output = apply_filters('wptexturize', $output);
        // $output = apply_filters('convert_chars', $output);

        if ($length_callback !== null) {
            remove_filter('excerpt_length', $length_callback);
        }

        if ($more_callback !== null) {
            remove_filter('excerpt_more', $more_callback);
        }

        return $output;
    }
}

if (! function_exists('strLimit')) {
    function strLimit(string $text, int $limit = 55, string $more = '...', string $limitType = 'words', bool $preserveWords = false): string
    {
        if ($limitType === 'characters') {
            // $limit = number of characters
            return Str::limit($text, $limit, $more, preserveWords: $preserveWords);
        } else {
            // $limit = number of words
            return wp_trim_words($text, $limit, $more);
        }
    }
}

if (! function_exists('getCustomPostTypeArchiveTitle')) {
    function getCustomPostTypeArchiveTitle(string $post_type, string $label = 'name')
    {
        $post_type_obj = get_post_type_object($post_type);
        if ($post_type_obj) {
            return $post_type_obj->labels->{$label};
        }

        return '';
    }
}
