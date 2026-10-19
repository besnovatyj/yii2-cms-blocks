<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks;

use Besnovatyj\Blocks\files\BlockFiles;
use Besnovatyj\Contracts\module\DeclaresModule;
use Besnovatyj\Contracts\module\ProvidesDirectories;
use Besnovatyj\Contracts\module\ProvidesMigrations;
use Besnovatyj\Kernel\module\CmsModule;

/**
 * Модуль блоков — управляемое из админки содержимое мест, которые объявляет тема.
 *
 * Тема не может заводить свои CRUD в админке. Она объявляет места (контракт
 * {@see \Besnovatyj\Contracts\theme\ThemeAreaCatalog}) и в разметке вызывает виджет
 * {@see \Besnovatyj\Blocks\widgets\BlockArea} с идентификатором места. Каркас места (обёртка, кнопки,
 * скрипты) остаётся в теме, отсюда приходит только начинка: блоки разных типов. Тип блока — схема
 * полей и шаблон вывода (встроенные типы, типы темы и типы модулей, см. readme).
 *
 * Это не шорткоды: шорткод — плейсхолдер внутри контента, который разворачивается в код, а место —
 * «дырка» в коде темы, которую администратор заполняет контентом. Механизмы компонуются: текстовые
 * шорткоды внутри блока разворачиваются при выводе.
 */
class Module extends CmsModule implements
    DeclaresModule,
    ProvidesDirectories,
    ProvidesMigrations
{
    public const bool EDITABLE = true;
    public const string MODULE_ID = 'Blocks';

    /**
     * Разрешение на код чужих виджетов (тип `embed`): создать такой блок и править его код.
     *
     * RBAC проекта маршрутный, поэтому разрешение — псевдомаршрут: контроллёра за ним нет, его
     * проверяет админка блоков. Маршрут вынесен из `default`, чтобы доступ к `/Blocks/backend/default/*`
     * не давал его заодно. Его дают шаблоны `/Blocks/backend/*` и `/Blocks/*`.
     */
    public const string EMBED_PERMISSION = '/Blocks/backend/embed/edit';

    public static function moduleId(): string { return self::MODULE_ID; }
    public static function isEditable(): bool { return self::EDITABLE; }
    public static function moduleConfig(): array { return require __DIR__ . '/config/config.php'; }
    public static function migrationPath(): string { return __DIR__ . '/migrations'; }
    public static function migrationNamespace(): ?string { return __NAMESPACE__ . '\\migrations'; }
    public static function directories(): array { return [BlockFiles::ORIGIN, BlockFiles::CACHE]; }
}
