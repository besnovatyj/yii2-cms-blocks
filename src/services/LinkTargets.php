<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\services;

use Besnovatyj\Blocks\schema\DataNormalizer;
use Besnovatyj\Contracts\menu\MenuTargetProvider;
use Besnovatyj\Kernel\module\ModuleFinder;

/**
 * Цели для поля `link` в форме блока: разделы сайта, которые модули объявляют для меню.
 *
 * Источник тот же, что у модуля меню, — контракт {@see MenuTargetProvider}, но без зависимости от
 * модуля меню: блоку нужен плоский список «раздел → кандидат», а не каскад и дерево пунктов.
 * Ссылка на цель хранится маршрутом и slug и потому переживает смену адреса раздела.
 */
final class LinkTargets
{
    /** @var array<string, array<string, string>>|null memo на запрос */
    private ?array $options = null;

    /**
     * Варианты для выпадающего списка: `подпись группы => [значение => подпись]`.
     *
     * Значение кодирует цель для формы ({@see DataNormalizer::encodeTarget()}).
     *
     * @return array<string, array<string, string>>
     */
    public function options(): array
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $options = [];
        foreach (ModuleFinder::implementing(MenuTargetProvider::class) as $module) {
            foreach ($module->menuTargets() as $target) {
                $route = ltrim($target->route, '/');
                foreach ($module->menuCandidates($target->route) as $slug => $label) {
                    $value = DataNormalizer::encodeTarget($route, [$target->slugParam => (string)$slug]);
                    $options[$target->label][$value] = (string)$label;
                }
            }
        }

        return $this->options = $options;
    }
}
