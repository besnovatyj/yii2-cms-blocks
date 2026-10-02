<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\migrations;

use Besnovatyj\Kernel\migration\BaseMigration;
use yii\base\NotSupportedException;

class m261002_100000_create_blocks_blocks_table extends BaseMigration
{
    public const string TABLE_NAME = '{{%blocks_blocks}}';

    /**
     * @throws NotSupportedException
     */
    public function safeUp(): void
    {
        parent::safeUp();

        if (!$this->existTable(static::TABLE_NAME)) {
            $this->createTable(static::TABLE_NAME, [
                'id' => $this->primaryKey(),
                'area' => $this->string(64)->notNull()
                    ->comment('Идентификатор места темы'),
                'title' => $this->string(255)->notNull()
                    ->comment('Название блока для админки'),
                'content' => $this->text()->notNull()
                    ->comment('Начинка места (HTML)'),
                'status' => $this->smallInteger(1)->notNull()->defaultValue(0)
                    ->comment('Статус: 0 — выключен, 1 — включён'),
                'show_from' => $this->dateTime()->null()
                    ->comment('Показывать с (null — без ограничения)'),
                'show_to' => $this->dateTime()->null()
                    ->comment('Показывать до (null — без ограничения)'),
                'sort_order' => $this->integer()->notNull()->defaultValue(0)
                    ->comment('Порядок внутри места'),
            ], $this->tableOptions);
            $this->addCommentOnTable(static::TABLE_NAME, 'Блоки мест темы');

            $this->createIndexes(static::TABLE_NAME, ['area', 'sort_order']);
            $this->createIndexes(static::TABLE_NAME, 'status');
        }

        parent::safeUp();
    }

}
