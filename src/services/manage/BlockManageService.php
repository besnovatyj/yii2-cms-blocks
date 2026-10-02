<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\services\manage;

use Besnovatyj\Blocks\entities\Block;
use Besnovatyj\Blocks\forms\backend\BlockForm;
use Besnovatyj\Blocks\readModels\AreaReadModel;
use Besnovatyj\Blocks\repositories\BlockRepository;
use Throwable;
use yii\db\Exception;
use yii\db\StaleObjectException;

/**
 * Сервис CRUD блоков. Любая правка сбрасывает кэш фронта ({@see AreaReadModel::invalidate()}).
 */
class BlockManageService
{
    public function __construct(private readonly BlockRepository $repo)
    {
    }

    /**
     * @throws Exception
     */
    public function create(BlockForm $form): Block
    {
        $entity = Block::create(
            $form->area,
            $form->title,
            $form->content,
            $form->status,
            BlockForm::dbDateTime($form->show_from),
            BlockForm::dbDateTime($form->show_to),
            $form->sort_order,
        );
        $this->repo->save($entity);
        AreaReadModel::invalidate();
        return $entity;
    }

    /**
     * @throws Exception
     */
    public function edit(int $id, BlockForm $form): void
    {
        $entity = $this->repo->get($id);
        $entity->edit(
            $form->area,
            $form->title,
            $form->content,
            $form->status,
            BlockForm::dbDateTime($form->show_from),
            BlockForm::dbDateTime($form->show_to),
            $form->sort_order,
        );
        $this->repo->save($entity);
        AreaReadModel::invalidate();
    }

    /**
     * Включить или выключить блок, не открывая форму.
     *
     * @throws Exception
     */
    public function setStatus(int $id, int $status): void
    {
        $entity = $this->repo->get($id);
        $entity->status = $status === Block::STATUS_ACTIVE ? Block::STATUS_ACTIVE : Block::STATUS_DRAFT;
        $this->repo->save($entity);
        AreaReadModel::invalidate();
    }

    /**
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function remove(int $id): void
    {
        $entity = $this->repo->get($id);
        $this->repo->remove($entity);
        AreaReadModel::invalidate();
    }
}
