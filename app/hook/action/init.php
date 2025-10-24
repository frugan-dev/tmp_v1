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
        // Initialize Laravel session for WordPress frontend requests.
        // This is necessary because Laravel routes automatically include the StartSession middleware,
        // but standard WordPress requests do not. Without this initialization, session data set in
        // Laravel routes (e.g. /_autosession/{key}) would not be available in WordPress pages.
        // We manually load the session ID from the cookie and start the session to maintain
        // session persistence across Laravel routes and WordPress requests.
        // https://dev.to/abdulwahidkahar/how-to-fix-session-store-not-set-on-request-laravel-11-2d4p
        'callback' => function (): void {
            if (is_admin()) {
                return;
            }

            $session = app('session.store');
            $request = \Illuminate\Http\Request::capture();

            // Set the session ID from the cookie
            $cookieName = config('session.cookie');
            if ($sessionId = $request->cookies->get($cookieName)) {
                $session->setId($sessionId);
            }

            // Start the session
            if (! $session->isStarted()) {
                $session->start();
            }
        },
        'accepted_args' => 0,
        'priority' => 1,
    ],
];
