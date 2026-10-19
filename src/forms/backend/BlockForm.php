<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\forms\backend;

use Besnovatyj\Blocks\entities\Block;
use Besnovatyj\Forms\BaseForm;
use Closure;

/**
 * Форма общих полей блока: место, тип, название, статус, окно показа, порядок.
 *
 * Данные блока по схеме типа — отдельная форма {@see BlockDataForm}: её поля зависят от типа и
 * появляются после первого сохранения, когда у блока есть папка файлов.
 *
 * Тип задаётся только при создании. Место и тип проверяются вместе: тип должен быть разрешён
 * местом ({@see self::$typesForArea}), и при переносе блока в другое место тоже.
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
    public string $type = '';
    public string $title = '';
    public int $status = Block::STATUS_DRAFT;
    public ?string $show_from = null;
    public ?string $show_to = null;
    public int $sort_order = 0;

    /**
     * @param Block|null        $block        редактируемый блок (null — новый)
     * @param list<string>|null $areaRange    допустимые места; null — любое корректное имя
     *                                        (пакета тем нет, места свободные)
     * @param Closure(string): list<string> $typesForArea id типов, которые принимает место
     */
    public function __construct(
        ?Block $block,
        private readonly ?array $areaRange,
        private readonly Closure $typesForArea,
        array $config = [],
    ) {
        if ($block) {
            $this->area = $block->area;
            $this->type = $block->type;
            $this->title = $block->title;
            $this->status = (int)$block->status;
            $this->show_from = $block->show_from !== null ? substr($block->show_from, 0, 16) : null;
            $this->show_to = $block->show_to !== null ? substr($block->show_to, 0, 16) : null;
            $this->sort_order = (int)$block->sort_order;
        }
        parent::__construct($config);
    }

    /** Сценарий создания: только в нём тип принимается из POST. */
    public const string SCENARIO_CREATE = 'create';

    public function rules(): array
    {
        return [
            [['area', 'title'], 'required'],
            ['area', 'string', 'max' => 64],
            ['area', 'match', 'pattern' => '/^[a-z0-9][a-z0-9_-]*$/i',
                'message' => 'Латиница, цифры, дефис и подчёркивание.'],
            ['area', 'in', 'range' => $this->areaRange ?? [], 'when' => fn () => $this->areaRange !== null,
                'message' => 'Активная тема не объявляет такое место.'],
            // Тип — атрибут только сценария создания: при правке он из POST не принимается.
            ['type', 'required', 'on' => self::SCENARIO_CREATE],
            ['type', 'string', 'max' => 64, 'on' => self::SCENARIO_CREATE],
            ['type', 'validateType', 'on' => self::SCENARIO_CREATE],
            ['area', 'validateType', 'except' => self::SCENARIO_CREATE],
            ['title', 'string', 'max' => 255],
            ['status', 'in', 'range' => array_keys(Block::statusList())],
            ['sort_order', 'integer'],
            [['show_from', 'show_to'], 'default', 'value' => null],
            [['show_from', 'show_to'], 'datetime', 'format' => 'php:' . self::DATE_FORMAT],
            ['show_to', 'compare', 'compareAttribute' => 'show_from', 'operator' => '>', 'type' => 'string',
                'when' => fn () => $this->show_from !== null,
                'message' => 'Конец показа должен быть позже начала.'],
        ];
    }

    /**
     * Тип разрешён выбранным местом. При создании ошибка ложится на тип, при правке — на место.
     */
    public function validateType(string $attribute): void
    {
        if (!in_array($this->type, ($this->typesForArea)($this->area), true)) {
            $this->addError($attribute, $attribute === 'type'
                ? 'Этот тип блока недоступен в выбранном месте.'
                : 'Выбранное место не принимает блоки этого типа.');
        }
    }

    public function attributeLabels(): array
    {
        return [
            'area' => 'Место',
            'type' => 'Тип',
            'title' => 'Название',
            'status' => 'Статус',
            'show_from' => 'Показывать с',
            'show_to' => 'Показывать до',
            'sort_order' => 'Порядок',
        ];
    }

    public function attributeHints(): array
    {
        return [
            'type' => 'Тип задаётся один раз: после создания его не сменить.',
            'title' => 'Видно только в админке.',
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
