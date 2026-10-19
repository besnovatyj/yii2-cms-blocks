<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\services\manage;

use Besnovatyj\Blocks\entities\Block;
use Besnovatyj\Blocks\files\BlockFiles;
use Besnovatyj\Blocks\forms\backend\BlockDataForm;
use Besnovatyj\Blocks\forms\backend\BlockForm;
use Besnovatyj\Blocks\readModels\AreaReadModel;
use Besnovatyj\Blocks\repositories\BlockRepository;
use Throwable;
use yii\db\Exception;
use yii\db\StaleObjectException;

/**
 * Сервис CRUD блоков. Любая правка сбрасывает кэш фронта ({@see AreaReadModel::invalidate()}).
 *
 * Папка файлов блока ({@see BlockFiles}) живёт вместе с блоком: создаётся при создании, удаляется
 * при удалении.
 */
class BlockManageService
{
    public function __construct(
        private readonly BlockRepository $repo,
        private readonly BlockFiles $files,
    ) {
    }

    /**
     * Новый блок с данными типа по умолчанию.
     *
     * @param array<string, mixed> $defaults данные нового блока по схеме типа
     * @throws Exception
     */
    public function create(BlockForm $form, array $defaults): Block
    {
        $entity = Block::create(
            $form->area,
            $form->type,
            $form->title,
            $defaults,
            $form->status,
            BlockForm::dbDateTime($form->show_from),
            BlockForm::dbDateTime($form->show_to),
            $form->sort_order,
        );
        $this->repo->save($entity);
        $this->files->ensure($entity->id);
        AreaReadModel::invalidate();
        return $entity;
    }

    /**
     * Общие поля и (если передана форма данных) данные блока.
     *
     * @param BlockDataForm|null $data null — данные не меняются (нет права править код `embed`)
     * @throws Exception
     */
    public function edit(int $id, BlockForm $form, ?BlockDataForm $data): void
    {
        $entity = $this->repo->get($id);
        $entity->edit(
            $form->area,
            $form->title,
            $form->status,
            BlockForm::dbDateTime($form->show_from),
            BlockForm::dbDateTime($form->show_to),
            $form->sort_order,
        );
        if ($data !== null) {
            $entity->fill($data->data);
        }
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
     * Блок и его папка файлов.
     *
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function remove(int $id): void
    {
        $entity = $this->repo->get($id);
        $this->repo->remove($entity);
        $this->files->remove($id);
        AreaReadModel::invalidate();
    }
}
