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
         * Use the generated theme.json file.
         *
         * @return string
         */
        'callback' => fn (Container $container, $path, $file) => $file === 'theme.json'
            ? public_path('build/assets/theme.json')
            : $path,
        'accepted_args' => 2,
    ],
];
