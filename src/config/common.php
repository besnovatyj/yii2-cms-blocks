<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\Blocks\Module;
use Besnovatyj\Blocks\readModels\AreaReadModel;
use Besnovatyj\Blocks\repositories\BlockRepository;
use Besnovatyj\Blocks\services\AreaDirectory;
use Besnovatyj\Contracts\theme\ThemeAreaCatalog;

/**
 * Yii2-конфиг модуля для движка yiisoft/config (группа `common` — общий для всех приложений).
 *
 * Объявляется через `extra.config-plugin`, собирается modman в merge-plan и мёржится в рантайме.
 * Регистрирует модуль. Меню админки — `adminMenu.php` (группа `admin-menu`), миграции — вклад modman. Значения берутся
 * из статических методов {@see Module} — единый источник, без дублирования.
 *
 * DI: {@see AreaDirectory} получает каталог мест темы, только если его биндинг есть (его вкладывает
 * пакет тем). Интерфейс без биндинга контейнер не создаст, поэтому решение принимается здесь, в
 * composition root, а не внутри сервиса. Замыкание ленивое: каталог читается, лишь когда админка
 * блоков реально его спрашивает.
 *
 * {@see AreaReadModel} — синглтон, чтобы все места страницы делили одну выборку на запрос: виджет
 * места создаётся заново на каждый вызов, и без синглтона каждый тянул бы кэш заново.
 */
return [
    'modules' => [
        Module::moduleId() => array_merge(
            ['class' => Module::class],
            Module::moduleConfig(),
            ['version' => Module::moduleVersion()],
        ),
    ],
    'container' => [
        'singletons' => [
            AreaReadModel::class => AreaReadModel::class,
            AreaDirectory::class => static fn (): AreaDirectory => new AreaDirectory(
                Yii::$container->has(ThemeAreaCatalog::class) ? Yii::$container->get(ThemeAreaCatalog::class) : null,
                Yii::$container->get(BlockRepository::class),
            ),
        ],
    ],
];
