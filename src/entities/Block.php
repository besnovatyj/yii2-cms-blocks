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
 * @property int         $id
 * @property string      $area       Идентификатор места темы.
 * @property string      $title      Название для админки (на сайт не выводится).
 * @property string      $content    Начинка места: HTML из редактора.
 * @property int         $status     {@see self::STATUS_DRAFT} или {@see self::STATUS_ACTIVE}.
 * @property string|null $show_from  Начало показа, `Y-m-d H:i:s` (null — без ограничения).
 * @property string|null $show_to    Конец показа, `Y-m-d H:i:s` (null — без ограничения).
 * @property int         $sort_order Порядок внутри места (меньше — выше).
 */
class Block extends ActiveRecord
{
    public const int STATUS_DRAFT = 0;
    public const int STATUS_ACTIVE = 1;

    public static function tableName(): string
    {
        return '{{%blocks_blocks}}';
    }

    public static function create(
        string $area,
        string $title,
        string $content,
        int $status,
        ?string $showFrom,
        ?string $showTo,
        int $sortOrder = 0,
    ): self {
        $entity = new static();
        $entity->edit($area, $title, $content, $status, $showFrom, $showTo, $sortOrder);
        return $entity;
    }

    public function edit(
        string $area,
        string $title,
        string $content,
        int $status,
        ?string $showFrom,
        ?string $showTo,
        int $sortOrder,
    ): void {
        $this->area = $area;
        $this->title = $title;
        $this->content = $content;
        $this->status = $status;
        $this->show_from = $showFrom;
        $this->show_to = $showTo;
        $this->sort_order = $sortOrder;
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
}
