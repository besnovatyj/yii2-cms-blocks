<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\types;

use Besnovatyj\Contracts\block\BlockType;
use Besnovatyj\Contracts\block\BlockTypeProvider;
use Besnovatyj\Contracts\theme\ThemeArea;
use Besnovatyj\Contracts\theme\ThemeBlockTypeCatalog;
use Besnovatyj\Kernel\module\ModuleFinder;
use Yii;

/**
 * Реестр типов блоков из трёх источников: встроенные ({@see BuiltInTypes}), модули
 * ({@see BlockTypeProvider}) и активная тема ({@see ThemeBlockTypeCatalog}).
 *
 * Id типа модуля — `{moduleId}:{id}`. По префиксу {@see get()} находит модуль-владельца напрямую, без
 * перебора модулей, а типы темы и модулей не сталкиваются. Типы темы — без префикса.
 *
 * Вывод блока зовёт только {@see get()}, и тот читает ровно один источник. Полный список
 * ({@see all()}, {@see allowedIn()}) нужен админке.
 */
final class BlockTypeRegistry
{
    /** Разделитель id модуля и id типа в полном id типа модуля. */
    public const string MODULE_SEPARATOR = ':';

    /** @var array<string, BlockType|null> memo {@see get()} на запрос */
    private array $resolved = [];

    /**
     * @param ThemeBlockTypeCatalog|null $theme каталог типов темы; null — пакета тем нет
     */
    public function __construct(private readonly ?ThemeBlockTypeCatalog $theme)
    {
    }

    /**
     * Тип по полному id; null — такого типа сейчас нет (сменили тему, удалили модуль).
     */
    public function get(string $id): ?BlockType
    {
        if (!array_key_exists($id, $this->resolved)) {
            $this->resolved[$id] = $this->resolve($id);
        }

        return $this->resolved[$id];
    }

    /**
     * Все доступные типы: встроенные, затем модулей, затем темы.
     *
     * @return array<string, BlockType> ключ — полный id
     */
    public function all(): array
    {
        $types = BuiltInTypes::all();

        foreach (ModuleFinder::implementing(BlockTypeProvider::class) as $moduleId => $module) {
            foreach ($module->blockTypes() as $type) {
                $full = $this->qualify((string)$moduleId, $type);
                $types[$full->id] = $full;
            }
        }

        foreach ($this->theme?->blockTypes() ?? [] as $id => $type) {
            if (isset($types[$id])) {
                Yii::warning("Theme block type '{$id}' clashes with a built-in type and is ignored", __METHOD__);
                continue;
            }
            $types[$id] = $type;
        }

        return $types;
    }

    /**
     * Типы, которые можно положить в место: встроенные (разрешены везде) и перечисленные местом.
     *
     * @param ThemeArea|null $area место; null — каталога мест нет или тема место не объявляет:
     *                             ограничений не знаем, доступны все типы
     * @return array<string, BlockType>
     */
    public function allowedIn(?ThemeArea $area): array
    {
        $all = $this->all();
        if ($area === null) {
            return $all;
        }

        $allowed = BuiltInTypes::all();
        foreach ($area->types as $id) {
            if (isset($all[$id])) {
                $allowed[$id] = $all[$id];
            }
        }

        return $allowed;
    }

    private function resolve(string $id): ?BlockType
    {
        $builtIn = BuiltInTypes::all()[$id] ?? null;
        if ($builtIn !== null) {
            return $builtIn;
        }

        if (!str_contains($id, self::MODULE_SEPARATOR)) {
            return $this->theme?->blockType($id);
        }

        [$moduleId, $local] = explode(self::MODULE_SEPARATOR, $id, 2);
        if (!Yii::$app->hasModule($moduleId)) {
            return null;
        }

        $module = Yii::$app->getModule($moduleId);
        if (!$module instanceof BlockTypeProvider) {
            return null;
        }

        $type = $module->blockTypes()[$local] ?? null;

        return $type === null ? null : $this->qualify($moduleId, $type);
    }

    /**
     * Тип модуля с полным id.
     */
    private function qualify(string $moduleId, BlockType $type): BlockType
    {
        return new BlockType(
            id: $moduleId . self::MODULE_SEPARATOR . $type->id,
            label: $type->label,
            view: $type->view,
            fields: $type->fields,
            hint: $type->hint,
            dataProvider: $type->dataProvider,
        );
    }
}
