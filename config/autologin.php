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
    'key' => env('AUTOLOGIN_KEY'),
    'expire' => env('AUTOLOGIN_EXPIRE'),
    'user_id' => (int) env('AUTOLOGIN_USER_ID'),
];
