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
    [
        'callback' => function (): void {
            if (! config('maintenance.enabled')) {
                return;
            }

            // Check if user is logged in
            if (is_user_logged_in()) {
                return;
            }

            sd(session()->all());

            // Check if autosession expire is active
            if (session()->has('autosession_expire')) {
                $expireStr = session('autosession_expire');

                if ($expireStr) {
                    $nowObj = \Illuminate\Support\Carbon::now();
                    $expireObj = \Illuminate\Support\Carbon::parse($expireStr);

                    if ($nowObj->lte($expireObj)) {
                        return;
                    } else {
                        session()->forget('autosession_expire');
                    }
                }
            }

            $allowedPaths = [
                '/_autologin/',
                '/_autosession/',
                '/wp-admin/',
                '/wp-login.php',
            ];

            $currentPath = $_SERVER['REQUEST_URI'] ?? '';

            foreach ($allowedPaths as $path) {
                if (str_starts_with($currentPath, $path)) {
                    return;
                }
            }

            status_header(503);
            nocache_headers();

            echo view('maintenance')->render();

            exit;
        },
        'accepted_args' => 0,
    ],
];
