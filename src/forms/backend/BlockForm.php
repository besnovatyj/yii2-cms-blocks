<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\forms\backend;

use Besnovatyj\Blocks\entities\Block;
use Besnovatyj\Forms\BaseForm;

/**
 * Форма создания/редактирования блока.
 *
 * Наследует {@see BaseForm}: скаляры из POST приводятся к typed-свойствам (нет TypeError).
 * Даты показа в форме — с точностью до минуты (`Y-m-d H:i`, формат виджета даты), в БД —
 * `Y-m-d H:i:s`; перевод делают {@see self::__construct()} и {@see self::dbDateTime()}.
 */
class BlockForm extends BaseForm
{
    /** Формат дат показа в форме. */
    public const string DATE_FORMAT = 'Y-m-d H:i';

    public string $area = '';
    public string $title = '';
    public string $content = '';
    public int $status = Block::STATUS_DRAFT;
    public ?string $show_from = null;
    public ?string $show_to = null;
    public int $sort_order = 0;

    /**
     * @param Block|null         $block     редактируемый блок (null — новый)
     * @param list<string>|null  $areaRange допустимые места; null — любое корректное имя
     *                                      (пакета тем нет, места свободные)
     */
    public function __construct(?Block $block = null, private readonly ?array $areaRange = null, array $config = [])
    {
        if ($block) {
            $this->area = $block->area;
            $this->title = $block->title;
            $this->content = $block->content;
            $this->status = (int)$block->status;
            $this->show_from = $block->show_from !== null ? substr($block->show_from, 0, 16) : null;
            $this->show_to = $block->show_to !== null ? substr($block->show_to, 0, 16) : null;
            $this->sort_order = (int)$block->sort_order;
        }
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [['area', 'title', 'content'], 'required'],
            ['area', 'string', 'max' => 64],
            ['area', 'match', 'pattern' => '/^[a-z0-9][a-z0-9_-]*$/i',
                'message' => 'Латиница, цифры, дефис и подчёркивание.'],
            ['area', 'in', 'range' => $this->areaRange ?? [], 'when' => fn () => $this->areaRange !== null,
                'message' => 'Активная тема не объявляет такое место.'],
            ['title', 'string', 'max' => 255],
            ['content', 'string'],
            ['status', 'in', 'range' => array_keys(Block::statusList())],
            ['sort_order', 'integer'],
            [['show_from', 'show_to'], 'default', 'value' => null],
            [['show_from', 'show_to'], 'datetime', 'format' => 'php:' . self::DATE_FORMAT],
            ['show_to', 'compare', 'compareAttribute' => 'show_from', 'operator' => '>', 'type' => 'string',
                'when' => fn () => $this->show_from !== null,
                'message' => 'Конец показа должен быть позже начала.'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'area' => 'Место',
            'title' => 'Название',
            'content' => 'Содержимое',
            'status' => 'Статус',
            'show_from' => 'Показывать с',
            'show_to' => 'Показывать до',
            'sort_order' => 'Порядок',
        ];
    }

    public function attributeHints(): array
    {
        return [
            'title' => 'Видно только в админке.',
            'content' => 'Каркас места (обёртка, кнопка закрытия) рисует тема. Сюда — только начинка: '
                . 'удобнее всего начать со сниппета темы из пикера редактора.',
            'show_from' => 'Пусто — с момента включения.',
            'show_to' => 'Пусто — без срока.',
            'sort_order' => 'Если в месте несколько блоков, они выводятся подряд: меньше — выше.',
        ];
    }

    /**
     * Дата показа в формате БД (секунды обнулены).
     */
    public static function dbDateTime(?string $value): ?string
    {
        return $value === null || $value === '' ? null : $value . ':00';
    }
}
