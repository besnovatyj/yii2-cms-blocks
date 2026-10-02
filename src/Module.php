<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks;

use Besnovatyj\Contracts\module\DeclaresModule;
use Besnovatyj\Contracts\module\ProvidesAdminMenu;
use Besnovatyj\Contracts\module\ProvidesMigrations;
use Besnovatyj\Kernel\module\CmsModule;

/**
 * Модуль блоков — управляемое из админки содержимое мест, которые объявляет тема.
 *
 * Тема не может заводить свои CRUD в админке. Она объявляет места (контракт
 * {@see \Besnovatyj\Contracts\theme\ThemeAreaCatalog}) и в разметке вызывает виджет
 * {@see \Besnovatyj\Blocks\widgets\BlockArea} с идентификатором места. Каркас места (обёртка, кнопки,
 * скрипты) остаётся в теме, отсюда приходит только начинка: HTML, набранный в редакторе, обычно
 * со вставленным сниппетом разметки темы.
 *
 * Это не шорткоды: шорткод — плейсхолдер внутри контента, который разворачивается в код, а место —
 * «дырка» в коде темы, которую администратор заполняет контентом. Механизмы компонуются: текстовые
 * шорткоды внутри блока разворачиваются при выводе.
 */
class Module extends CmsModule implements
    DeclaresModule,
    ProvidesMigrations,
    ProvidesAdminMenu
{
    public const bool EDITABLE = true;
    public const string VERSION = '1.0.0';
    public const string MODULE_ID = 'Blocks';

    public static function moduleId(): string { return self::MODULE_ID; }
    public static function moduleVersion(): string { return self::VERSION; }
    public static function isEditable(): bool { return self::EDITABLE; }
    public static function adminMenu(): array { return require __DIR__ . '/config/adminMenu.php'; }
    public static function moduleConfig(): array { return require __DIR__ . '/config/config.php'; }
    public static function migrationPath(): string { return __DIR__ . '/migrations'; }
    public static function migrationNamespace(): ?string { return __NAMESPACE__ . '\\migrations'; }
}
