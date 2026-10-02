<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\widgets;

use Besnovatyj\Blocks\Module;
use Besnovatyj\Blocks\readModels\AreaReadModel;
use Besnovatyj\Contracts\shortcode\ShortcodeTextResolver;
use Yii;
use yii\base\Widget;

/**
 * Начинка места темы: блоки, которые администратор положил в место и которые видны сейчас.
 *
 * Тема вызывает виджет в своей разметке и сама рисует каркас вокруг. Пустая строка означает
 * «показывать нечего», и тема по ней прячет обёртку целиком:
 * ```php
 * <?php $content = BlockArea::widget(['area' => 'popup-bar']); ?>
 * <?php if ($content !== ''): ?>
 *     <div class="…"><?= $content ?><button …>×</button></div>
 * <?php endif; ?>
 * ```
 *
 * Несколько видимых блоков выводятся подряд в порядке сортировки. Текстовые шорткоды (`%name%`)
 * разворачиваются, если подключён модуль шорткодов (контракт {@see ShortcodeTextResolver}); виджетные
 * (`[widget …]`) — нет: место выводит контент, а не собирает страницу.
 *
 * Модуль блоков не установлен — виджет молча отдаёт пустоту, а не роняет страницу на запросе к
 * несуществующей таблице.
 */
class BlockArea extends Widget
{
    /** Идентификатор места, объявленный темой. */
    public string $area = '';

    /**
     * Строчное место: начинка встаёт в строку разметки темы (копирайт, заголовок окна).
     *
     * Редактор оборачивает любой текст в абзац, а `<p>` внутри строки ломает её переносом. При
     * `true` обёртка снимается, если блок — ровно один абзац без атрибутов; всё сложнее остаётся
     * как есть, потому что тогда разметку явно задумал автор.
     */
    public bool $inline = false;

    public function __construct(private readonly AreaReadModel $reader, $config = [])
    {
        parent::__construct($config);
    }

    public function run(): string
    {
        if ($this->area === '' || !Yii::$app->hasModule(Module::MODULE_ID)) {
            return '';
        }

        $parts = [];
        foreach ($this->reader->contents($this->area) as $content) {
            $content = trim($this->resolveShortcodes($content));
            if ($content === '') {
                continue;
            }
            $parts[] = $this->inline ? $this->unwrapParagraph($content) : $content;
        }

        return implode("\n", $parts);
    }

    /**
     * Текстовые шорткоды, если модуль шорткодов подключён; иначе строка как есть.
     */
    private function resolveShortcodes(string $content): string
    {
        if (!Yii::$app->has('shortcode')) {
            return $content;
        }

        $resolver = Yii::$app->get('shortcode');

        return $resolver instanceof ShortcodeTextResolver ? $resolver->resolveText($content) : $content;
    }

    /**
     * Снять обёртку с единственного абзаца без атрибутов.
     */
    private function unwrapParagraph(string $content): string
    {
        if (preg_match('~^<p>((?:(?!</?p[\s>]).)*)</p>$~is', $content, $matches) === 1) {
            return trim($matches[1]);
        }

        return $content;
    }
}
