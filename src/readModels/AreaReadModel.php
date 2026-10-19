<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\readModels;

use Besnovatyj\Blocks\entities\Block;
use DateTimeImmutable;
use Yii;
use yii\caching\TagDependency;

/**
 * Чтение блоков для фронта: что сейчас показывать в месте темы.
 *
 * Мест на странице несколько (полоса, окно, колонки подвала), поэтому все включённые блоки грузятся
 * одним элементом кэша на все места сразу (APCu) с тегом {@see self::CACHE_TAG}, а не запросом на
 * каждое место. Инвалидация — из {@see \Besnovatyj\Blocks\services\manage\BlockManageService} при
 * любой правке блока.
 *
 * Окно показа (`show_from`/`show_to`) в кэш не зашито: оно проверяется на каждый запрос по уже
 * загруженным строкам. Иначе блок, у которого наступила дата, ждал бы сброса кэша.
 *
 * Строки хранятся массивами, а не AR-объектами: так они кладутся в кэш без сериализации моделей.
 * В кэше — данные блоков, а не готовый HTML: у типов модулей данные живые, их кэширует поставщик.
 */
final class AreaReadModel
{
    /** Ключ меняется вместе с форматом строк: новый код не должен читать кэш старого формата. */
    public const string CACHE_KEY = 'blocks.active.v2';
    public const string CACHE_TAG = 'blocks';

    /** @var array<string, list<array{id:int, type:string, data:array, show_from:?string, show_to:?string}>>|null */
    private ?array $byArea = null;

    /**
     * Блоки, видимые в месте сейчас, в порядке вывода.
     *
     * @param string                 $area идентификатор места
     * @param DateTimeImmutable|null $now  момент проверки окна показа (null — сейчас)
     * @return list<array{id:int, type:string, data:array<string, mixed>}>
     */
    public function blocks(string $area, ?DateTimeImmutable $now = null): array
    {
        $moment = ($now ?? new DateTimeImmutable())->format('Y-m-d H:i:s');

        $result = [];
        foreach ($this->byArea()[$area] ?? [] as $row) {
            if ($row['show_from'] !== null && $row['show_from'] > $moment) {
                continue;
            }
            if ($row['show_to'] !== null && $row['show_to'] < $moment) {
                continue;
            }
            $result[] = ['id' => $row['id'], 'type' => $row['type'], 'data' => $row['data']];
        }

        return $result;
    }

    /**
     * Сброс кэша. Отсутствие кэша — не ошибка.
     */
    public static function invalidate(): void
    {
        if (Yii::$app->has('cache') && Yii::$app->cache !== null) {
            TagDependency::invalidate(Yii::$app->cache, [self::CACHE_TAG]);
        }
    }

    /**
     * Включённые блоки, сгруппированные по местам (memo на запрос поверх кэша).
     *
     * @return array<string, list<array{id:int, type:string, data:array, show_from:?string, show_to:?string}>>
     */
    private function byArea(): array
    {
        if ($this->byArea !== null) {
            return $this->byArea;
        }

        // Кэш может быть не сконфигурирован (например, в консоли) — тогда читаем напрямую:
        // корректность важнее скорости.
        $rows = Yii::$app->has('cache') && Yii::$app->cache !== null
            ? Yii::$app->cache->getOrSet(
                self::CACHE_KEY,
                fn (): array => $this->loadRows(),
                null,
                new TagDependency(['tags' => [self::CACHE_TAG]]),
            )
            : $this->loadRows();

        $byArea = [];
        foreach ($rows as $row) {
            $byArea[(string)$row['area']][] = [
                'id' => (int)$row['id'],
                'type' => (string)$row['type'],
                'data' => $row['data'],
                'show_from' => $row['show_from'] !== null ? (string)$row['show_from'] : null,
                'show_to' => $row['show_to'] !== null ? (string)$row['show_to'] : null,
            ];
        }

        return $this->byArea = $byArea;
    }

    /**
     * Включённые блоки в порядке вывода; окно показа не учитывается (см. шапку класса).
     *
     * `asArray()` не приводит типы колонок, поэтому JSON декодируется здесь — один раз до кэша.
     *
     * @return list<array{id:int, area:string, type:string, data:array, show_from:?string, show_to:?string}>
     */
    private function loadRows(): array
    {
        $rows = Block::find()
            ->select(['id', 'area', 'type', 'data', 'show_from', 'show_to'])
            ->where(['status' => Block::STATUS_ACTIVE])
            ->orderBy(['area' => SORT_ASC, 'sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->asArray()
            ->all();

        foreach ($rows as &$row) {
            $data = is_string($row['data']) ? json_decode($row['data'], true) : $row['data'];
            $row['data'] = is_array($data) ? $data : [];
        }
        unset($row);

        return $rows;
    }
}
