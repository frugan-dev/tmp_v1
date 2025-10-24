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

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

if (config('autologin.key') && config('autologin.expire') && config('autologin.user_id')) {
    Route::get('/_autologin/{key}', function (Request $request, string $key) {
        if ($key && $key === config('autologin.key') && ! is_user_logged_in()) {
            $nowObj = Carbon::now();
            $expireObj = Carbon::parse(config('autologin.expire'));

            if ($nowObj->lte($expireObj)) {
                $userId = config('autologin.user_id');

                $user = wp_set_current_user($userId);
                wp_set_auth_cookie($userId);

                if ($user) {
                    do_action('wp_login', $user->user_login, $user);
                    Log::info('Autologin successful for user ID: '.$userId);
                } else {
                    Log::error('Failed to autologin user ID: '.$userId);
                }
            } else {
                Log::warning('Autologin link expired');
            }
        }

        return redirect('/');
    })->name('autologin');
}

if (config('autosession.key') && config('autosession.expire')) {
    Route::get('/_autosession/{key}', function (Request $request, string $key) {
        if ($key && $key === config('autosession.key')) {
            $nowObj = Carbon::now();
            $expireObj = Carbon::parse(config('autosession.expire'));

            if ($nowObj->lte($expireObj)) {
                session(['autosession.expire' => $expireObj->toDateTimeString()]);

                Log::info('Autosession successful created');
            } else {
                Log::warning('Autosession link expired');
            }
        }

        return redirect('/');
    })->name('autosession');
}
