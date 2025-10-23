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

namespace App;

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', fn () => sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage')));
