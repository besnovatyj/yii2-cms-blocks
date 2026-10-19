<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\schema;

/**
 * Вид поля схемы типа блока: определяет, как значение хранится в `data`, как выглядит в форме и как
 * проверяется.
 */
enum FieldKind: string
{
    /** Строка в одну линию. */
    case Text = 'text';
    /** Многострочный текст без разметки. */
    case Textarea = 'textarea';
    /** HTML из редактора; текстовые шорткоды разворачиваются при выводе. */
    case Richtext = 'richtext';
    /** Файл из папки блока + альтернативный текст: `{file, alt}`. */
    case Image = 'image';
    /** Ссылка: свободный URL или цель модуля: `{url, route, params}`. */
    case Link = 'link';
    /** Значение из списка `options` схемы. */
    case Select = 'select';
    /** Флажок. */
    case Checkbox = 'checkbox';
    /** Список строк с вложенными полями. */
    case Repeater = 'repeater';
    /** Код чужого виджета: выводится как есть, правится только с разрешением. */
    case Code = 'code';
}
