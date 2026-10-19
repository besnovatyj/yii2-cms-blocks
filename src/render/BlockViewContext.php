<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\render;

use Besnovatyj\Blocks\files\BlockFiles;
use Besnovatyj\Contracts\shortcode\ShortcodeTextResolver;
use Yii;
use yii\helpers\Url;

/**
 * Помощник шаблона типа блока: переменная `$block` в каждом шаблоне.
 *
 * Значения полей шаблон берёт из `$data` и превращает в разметку через этот объект: URL картинки
 * из папки блока, адрес ссылки, HTML с развёрнутыми шорткодами. Так шаблоны темы не знают, где лежат
 * файлы блока и как устроены цели ссылок.
 *
 * ```php
 * <img src="<?= Html::encode($block->imageUrl($data['image'])) ?>" alt="<?= Html::encode($data['image']['alt']) ?>">
 * <a href="<?= Html::encode($block->linkUrl($data['link'])) ?>">…</a>
 * <?= $block->html($data['text']) ?>
 * ```
 */
final readonly class BlockViewContext
{
    public function __construct(
        public int $id,
        public string $type,
        public string $area,
        private BlockFiles $files,
    ) {
    }

    /**
     * URL изображения из поля `image`; пустая строка — изображение не выбрано.
     *
     * @param array{file?:string} $image
     */
    public function imageUrl(array $image): string
    {
        return $this->files->url($this->id, (string)($image['file'] ?? ''));
    }

    /**
     * Адрес из поля `link`; пустая строка — ссылки нет.
     *
     * @param array{url?:string, route?:string, params?:array<string,string>} $link
     */
    public function linkUrl(array $link): string
    {
        $route = (string)($link['route'] ?? '');
        if ($route !== '') {
            return Url::to(array_merge(['/' . ltrim($route, '/')], (array)($link['params'] ?? [])));
        }

        return (string)($link['url'] ?? '');
    }

    /**
     * HTML из поля `richtext` с развёрнутыми текстовыми шорткодами (`%name%`), если подключён модуль
     * шорткодов. Виджетные шорткоды не разворачиваются: место выводит контент, а не собирает страницу.
     */
    public function html(string $html): string
    {
        if ($html === '' || !Yii::$app->has('shortcode')) {
            return $html;
        }

        $resolver = Yii::$app->get('shortcode');

        return $resolver instanceof ShortcodeTextResolver ? $resolver->resolveText($html) : $html;
    }
}
