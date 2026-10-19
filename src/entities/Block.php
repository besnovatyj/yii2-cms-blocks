<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\entities;

use yii\db\ActiveRecord;

/**
 * Блок — начинка места темы, которой управляет администратор.
 *
 * В одном месте может лежать несколько блоков: они выводятся подряд по {@see $sort_order}. Так
 * объявления можно заготовить заранее и развести по датам показа, не трогая работающее.
 *
 * Содержимое блока — данные по схеме его типа ({@see $data}), разметку рисует шаблон типа. Тип
 * задаётся при создании и не меняется: данные разных типов несовместимы, смена типа молча теряла бы
 * поля.
 *
 * @property int                  $id
 * @property string               $area       Идентификатор места темы.
 * @property string               $type       Полный id типа блока.
 * @property string               $title      Название для админки (на сайт не выводится).
 * @property array<string, mixed> $data       Данные блока по схеме типа (JSON-колонка).
 * @property int                  $status     {@see self::STATUS_DRAFT} или {@see self::STATUS_ACTIVE}.
 * @property string|null          $show_from  Начало показа, `Y-m-d H:i:s` (null — без ограничения).
 * @property string|null          $show_to    Конец показа, `Y-m-d H:i:s` (null — без ограничения).
 * @property int                  $sort_order Порядок внутри места (меньше — выше).
 */
class Block extends ActiveRecord
{
    public const int STATUS_DRAFT = 0;
    public const int STATUS_ACTIVE = 1;

    public static function tableName(): string
    {
        return '{{%blocks_blocks}}';
    }

    /**
     * @param array<string, mixed> $data начальные данные по схеме типа
     */
    public static function create(
        string $area,
        string $type,
        string $title,
        array $data,
        int $status,
        ?string $showFrom,
        ?string $showTo,
        int $sortOrder = 0,
    ): self {
        $entity = new static();
        $entity->type = $type;
        $entity->data = $data;
        $entity->edit($area, $title, $status, $showFrom, $showTo, $sortOrder);
        return $entity;
    }

    /**
     * Общие поля блока. Тип не меняется, данные — {@see self::fill()}.
     */
    public function edit(
        string $area,
        string $title,
        int $status,
        ?string $showFrom,
        ?string $showTo,
        int $sortOrder,
    ): void {
        $this->area = $area;
        $this->title = $title;
        $this->status = $status;
        $this->show_from = $showFrom;
        $this->show_to = $showTo;
        $this->sort_order = $sortOrder;
    }

    /**
     * Данные блока по схеме типа.
     *
     * @param array<string, mixed> $data
     */
    public function fill(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Подписи статусов.
     *
     * @return array<int, string>
     */
    public static function statusList(): array
    {
        return [
            self::STATUS_DRAFT => 'Выключен',
            self::STATUS_ACTIVE => 'Включён',
        ];
    }

    public function isActive(): bool
    {
        return (int)$this->status === self::STATUS_ACTIVE;
    }

    /**
     * Данные блока массивом, даже если колонка пришла строкой (драйвер без JSON-приведения).
     *
     * @return array<string, mixed>
     */
    public function dataArray(): array
    {
        $data = $this->data;
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return is_array($data) ? $data : [];
    }
}
