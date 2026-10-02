<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\services;

use Besnovatyj\Blocks\repositories\BlockRepository;
use Besnovatyj\Contracts\theme\ThemeArea;
use Besnovatyj\Contracts\theme\ThemeAreaCatalog;

/**
 * Справочник мест для админки: что объявляет активная тема и где уже лежат блоки.
 *
 * Сводит два источника. Объявленные темой места ({@see ThemeAreaCatalog}) идут первыми и в порядке
 * темы, даже пустые: администратор видит, что ещё можно заполнить. Места, где блоки есть, а тема их
 * не объявляет (сменили тему, переименовали место), идут следом с пометкой: такие блоки на сайт
 * не попадают, пока активная тема не вызовет место с этим идентификатором.
 */
final class AreaDirectory
{
    /**
     * @param ThemeAreaCatalog|null $catalog каталог мест темы; null — пакета тем нет, места свободные
     * @param BlockRepository       $blocks  источник фактически занятых мест
     */
    public function __construct(
        private readonly ?ThemeAreaCatalog $catalog,
        private readonly BlockRepository $blocks,
    ) {
    }

    /**
     * Все известные места: объявленные темой и занятые блоками.
     *
     * @return list<array{area:ThemeArea, declared:bool, total:int, active:int}>
     */
    public function rows(): array
    {
        $declared = $this->declared();
        $counts = $this->blocks->countsByArea();

        $rows = [];
        foreach ($declared as $id => $area) {
            $rows[] = [
                'area' => $area,
                'declared' => true,
                'total' => $counts[$id]['total'] ?? 0,
                'active' => $counts[$id]['active'] ?? 0,
            ];
        }

        foreach ($counts as $id => $count) {
            if (isset($declared[$id])) {
                continue;
            }
            $rows[] = [
                'area' => new ThemeArea(id: $id, label: $id),
                'declared' => false,
                'total' => $count['total'],
                'active' => $count['active'],
            ];
        }

        return $rows;
    }

    /**
     * Варианты для выбора места в форме и фильтре: `id => подпись`.
     *
     * @param string|null $current значение, которое должно остаться в списке, даже если тема его
     *                             не объявляет (у редактируемого блока)
     * @return array<string, string>
     */
    public function options(?string $current = null): array
    {
        $options = [];
        foreach ($this->rows() as $row) {
            $options[$row['area']->id] = $this->label($row['area'], $row['declared']);
        }

        if ($current !== null && $current !== '' && !isset($options[$current])) {
            $options[$current] = $this->label(new ThemeArea(id: $current, label: $current), false);
        }

        return $options;
    }

    /**
     * Объявляет ли активная тема место. Без каталога тем любое место считается допустимым.
     */
    public function isDeclared(string $id): bool
    {
        return $this->catalog === null || isset($this->declared()[$id]);
    }

    /**
     * Есть ли у модуля каталог мест (установлен пакет тем).
     */
    public function hasCatalog(): bool
    {
        return $this->catalog !== null;
    }

    /**
     * @return array<string, ThemeArea>
     */
    private function declared(): array
    {
        return $this->catalog?->areas() ?? [];
    }

    private function label(ThemeArea $area, bool $declared): string
    {
        $label = $area->label === $area->id ? $area->id : "{$area->label} ({$area->id})";

        return $declared ? $label : $label . ' — тема не объявляет';
    }
}
