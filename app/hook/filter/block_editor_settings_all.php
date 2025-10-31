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
use Illuminate\Support\Facades\Vite;

if (! defined('WPINC')) {
    exit;
}

return [
    [
        /**
         * Inject styles into the block editor.
         *
         * @return array
         */
        'callback' => function (Container $container, $settings) {
            $style = Vite::asset('resources/assets/css/editor.css');

            $settings['styles'][] = [
                'css' => "@import url('{$style}')",
            ];

            return $settings;
        },
    ],
];
