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

return [
    'name' => ! empty($value = get_field('short_name', 'option')) ? $value : (! empty($value = get_field('name', 'option')) ? $value : get_bloginfo('name', 'display')),

    'locale' => env('APP_LOCALE', substr(get_locale(), 0, 2)),

    // request()->host()
    // eg. domain.tld, www.domain.tld, dev.domain.tld, etc.
    'base_url' => $_SERVER['HTTP_HOST'] ?? \Safe\parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST),

    // eg. domain.tld
    'very_base_url' => str_replace(['www.', 'dev.'], '', \Safe\parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),

    // eg. www.domain.tld
    'www_url' => str_replace(['dev.'], 'www.', \Safe\parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
];
