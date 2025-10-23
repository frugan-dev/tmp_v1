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

use Illuminate\Container\Container;

class HookLoader
{
    private array $actions = [];

    private array $filters = [];

    private array $map = [];

    public function __construct(private Container $container) {}

    public function run(): void
    {
        $this->map = require get_theme_file_path('app/hook/map.php');

        $this->loadHooks(get_theme_file_path('app/hook/action'), 'action');
        $this->loadHooks(get_theme_file_path('app/hook/filter'), 'filter');

        $this->registerHooks();
    }

    private function loadHooks(string $directory, string $type): void
    {
        foreach (glob($directory.'/*.php') as $filename) {
            $name = basename($filename, '.php');
            $hookName = $this->map[$name] ?? $name;
            $result = require $filename;

            if ($type === 'action') {
                $target = &$this->actions;
            } else {
                $target = &$this->filters;
            }

            if (! isset($target[$hookName])) {
                $target[$hookName] = [];
            }

            if (is_array($result)) {
                $target[$hookName] = array_merge($target[$hookName], $result);
            } elseif (is_callable($result)) {
                $target[$hookName][] = ['callback' => $result];
            }
        }
    }

    private function registerHooks(): void
    {
        $this->processHooks($this->actions, 'action');
        $this->processHooks($this->filters, 'filter');
    }

    private function processHooks(array $hooks, string $type): void
    {
        foreach ($hooks as $hook => $items) {
            foreach ($items as $item) {
                $originalCallback = $item['callback'];
                $priority = $item['priority'] ?? 10;
                $accepted_args = $item['accepted_args'] ?? 1;
                $remove = $item['remove'] ?? false;

                if (is_array($originalCallback) && is_string($originalCallback[0])) {
                    $instance = $this->container->make($originalCallback[0]);
                    $callback = [$instance, $originalCallback[1]];
                } elseif (is_string($originalCallback)) {
                    $callback = $originalCallback;
                } else {
                    $callback = fn (...$args) => $originalCallback($this->container, ...$args);
                }

                if ($remove) {
                    $type === 'action'
                        ? remove_action($hook, $callback, $priority)
                        : remove_filter($hook, $callback, $priority);
                } else {
                    $type === 'action'
                        ? add_action($hook, $callback, $priority, $accepted_args)
                        : add_filter($hook, $callback, $priority, $accepted_args);
                }
            }
        }
    }
}
