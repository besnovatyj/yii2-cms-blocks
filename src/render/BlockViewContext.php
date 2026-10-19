<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\render;

use Besnovatyj\Blocks\files\BlockFiles;
use Besnovatyj\Contracts\shortcode\ShortcodeTextResolver;
use Yii;
use yii\helpers\Html;
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
 * <a href="<?= Html::encode($block->linkUrl($data['link'])) ?>"<?= $block->linkAttributes($data['link']) ?>>…</a>
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
     * Атрибуты тега `<a>` для поля `link`, кроме `href`: строка с ведущим пробелом или пустая.
     *
     * Ссылка с флажком «в новой вкладке» получает `target="_blank" rel="nofollow noopener"`:
     * посетитель не уходит с сайта, чужая страница не получает доступ к нашей вкладке
     * (`noopener`), а ссылка не передаёт вес стороннему сайту (`nofollow`). Без флажка — пусто.
     *
     * ```php
     * <a href="<?= Html::encode($block->linkUrl($data['link'])) ?>"<?= $block->linkAttributes($data['link']) ?>>…</a>
     * ```
     *
     * Для `Html::a()` и других хелперов — {@see linkOptions()}.
     *
     * @param array{blank?:bool} $link
     */
    public function linkAttributes(array $link): string
    {
        return Html::renderTagAttributes($this->linkOptions($link));
    }

    /**
     * То же, что {@see linkAttributes()}, массивом опций для `Html::a()` и прочих хелперов Yii.
     *
     * @param array{blank?:bool} $link
     * @return array<string, string>
     */
    public function linkOptions(array $link): array
    {
        return !empty($link['blank']) ? ['target' => '_blank', 'rel' => 'nofollow noopener'] : [];
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
