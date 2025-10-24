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

use Illuminate\Container\Container;

if (! defined('WPINC')) {
    exit;
}

return [
    [
        /**
         * Register the theme sidebars.
         *
         * @return void
         */
        'callback' => static function (Container $container): void {
            $config = [
                'before_widget' => '<section class="widget %1$s %2$s">',
                'after_widget' => '</section>',
                'before_title' => '<h3>',
                'after_title' => '</h3>',
            ];

            register_sidebar([
                'name' => __('Primary', 'sage'),
                'id' => 'sidebar-primary',
            ] + $config);

            if (function_exists('is_woocommerce')) {
                register_sidebar([
                    'name' => __('Shop', 'sage'),
                    'id' => 'sidebar-shop',
                ] + $config);
            }

            register_sidebar([
                'name' => __('Footer', 'sage'),
                'id' => 'sidebar-footer',
            ] + $config);
        },
        'accepted_args' => 0,
    ],
];
