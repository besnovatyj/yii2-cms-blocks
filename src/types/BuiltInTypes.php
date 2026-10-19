<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\types;

use Besnovatyj\Contracts\block\BlockType;

/**
 * Типы, встроенные в модуль блоков. Разрешены в любом месте темы.
 *
 * - `html` — HTML из редактора; это всё, чем был блок до появления типов.
 * - `embed` — код чужого виджета, выводится как есть. Создать блок и править его код можно только с
 *   разрешением {@see \Besnovatyj\Blocks\Module::EMBED_PERMISSION}.
 *
 * Шаблоны лежат в `src/views/blockTypes/`, тема перекрывает их обычным overlay представлений модуля:
 * `themes/{тема}/modules/Blocks/views/blockTypes/{type}.php`.
 */
final class BuiltInTypes
{
    public const string HTML = 'html';
    public const string EMBED = 'embed';

    /**
     * @return array<string, BlockType>
     */
    public static function all(): array
    {
        $views = dirname(__DIR__) . '/views/blockTypes';

        return [
            self::HTML => new BlockType(
                id: self::HTML,
                label: 'HTML',
                view: $views . '/html.php',
                fields: [
                    'html' => ['kind' => 'richtext', 'label' => 'Содержимое', 'required' => true],
                ],
                hint: 'Текст и разметка из редактора. Удобнее всего начать со сниппета темы.',
            ),
            self::EMBED => new BlockType(
                id: self::EMBED,
                label: 'Чужой виджет (код)',
                view: $views . '/embed.php',
                fields: [
                    'code' => [
                        'kind' => 'code',
                        'label' => 'Код виджета',
                        'required' => true,
                        'hint' => 'Код со стороннего сайта вставляется как есть, вместе со скриптами. '
                            . 'Проверяйте источник: этот код выполняется у каждого посетителя.',
                    ],
                ],
                hint: 'Код виджета со стороннего сайта. Нужно отдельное разрешение.',
            ),
        ];
    }
}
