<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\repositories;

use Besnovatyj\Blocks\entities\Block;
use RuntimeException;
use Throwable;
use yii\db\Exception;
use yii\db\StaleObjectException;

/**
 * CRUD-репозиторий блоков (админка). Фронт читает через {@see \Besnovatyj\Blocks\readModels\AreaReadModel}.
 */
class BlockRepository
{
    public function get(int $id): Block
    {
        if (!$entity = Block::findOne($id)) {
            throw new NotFoundException('Block is not found.');
        }
        return $entity;
    }

    /**
     * @throws Exception
     */
    public function save(Block $entity): void
    {
        if (!$entity->save()) {
            throw new RuntimeException('Block saving error.');
        }
    }

    /**
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function remove(Block $entity): void
    {
        if (!$entity->delete()) {
            throw new RuntimeException('Block removing error.');
        }
    }

    /**
     * Сколько блоков лежит в каждом месте, без фильтра по статусу и датам.
     *
     * @return array<string, array{total:int, active:int}> ключ — идентификатор места
     */
    public function countsByArea(): array
    {
        $rows = Block::find()
            ->select(['area', 'total' => 'COUNT(*)', 'active' => 'SUM([[status]] = ' . Block::STATUS_ACTIVE . ')'])
            ->groupBy('area')
            ->asArray()
            ->all();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string)$row['area']] = ['total' => (int)$row['total'], 'active' => (int)$row['active']];
        }

        return $counts;
    }
}
