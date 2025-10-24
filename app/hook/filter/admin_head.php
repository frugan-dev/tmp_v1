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

use Illuminate\Support\Facades\Vite;

if (! defined('WPINC')) {
    exit;
}

return [
    [
        /**
         * Inject scripts into the block editor.
         *
         * @return void
         */
        'callback' => function (): void {
            if (! get_current_screen()?->is_block_editor()) {
                return;
            }

            $dependencies = json_decode(Vite::content('editor.deps.json'));

            foreach ($dependencies as $dependency) {
                if (! wp_script_is($dependency)) {
                    wp_enqueue_script($dependency);
                }
            }

            echo Vite::withEntryPoints([
                'resources/js/editor.js',
            ])->toHtml();
        },
        'accepted_args' => 0,
    ],
];
