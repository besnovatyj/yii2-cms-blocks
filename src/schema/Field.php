<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\schema;

use Closure;
use InvalidArgumentException;

/**
 * Поле схемы типа блока, разобранное из массива-описания.
 *
 * Описание поля в схеме:
 * ```php
 * 'name' => [
 *     'kind'      => 'text',          // FieldKind, обязательно
 *     'label'     => 'Заголовок',     // по умолчанию — имя поля
 *     'hint'      => '…',
 *     'required'  => false,
 *     'default'   => '',              // значение нового блока (для text/textarea/richtext/select/checkbox)
 *     'maxLength' => 255,             // text, textarea
 *     'options'   => ['left' => 'Слева', 'right' => 'Справа'], // select, обязательно (массив или Closure)
 *     'min' => 0, 'max' => 3,         // repeater: границы числа строк
 *     'fields'    => [...],           // repeater: поля строки
 * ]
 * ```
 *
 * Варианты select из БД модуля (витрины, рубрики) передаются замыканием: `'options' => fn () => […]`.
 * Тогда список загружается только формой и проверкой при сохранении, а при выводе блока значение
 * берётся как сохранено — без запроса на каждый показ страницы. Пропавший с тех пор вариант
 * (удалённая рубрика) — забота шаблона или поставщика данных: они просто ничего не найдут.
 *
 * Ограничения repeater: строки не вкладываются друг в друга, а `richtext` и `code` внутри строки
 * недоступны. Строки добавляются в форму HTMX-запросом, а инициализировать редактор во вставленном
 * фрагменте надёжно нельзя; для текста в строке есть `textarea`.
 */
final readonly class Field
{
    /** Допустимое имя поля: оно становится ключом в `data` и частью имени input'а. */
    private const string NAME_PATTERN = '/^[a-zA-Z][a-zA-Z0-9_]*$/';

    /** Виды, недоступные внутри строки repeater. */
    private const array NOT_IN_REPEATER = [FieldKind::Repeater, FieldKind::Richtext, FieldKind::Code];

    /**
     * @param array<string, string>                 $options       варианты select (статические)
     * @param list<Field>                           $fields
     * @param (Closure(): array<string, string>)|null $optionsLoader варианты select, загружаемые лениво
     */
    public function __construct(
        public string $name,
        public FieldKind $kind,
        public string $label,
        public string $hint = '',
        public bool $required = false,
        public mixed $default = null,
        public ?int $maxLength = null,
        public array $options = [],
        public int $min = 0,
        public ?int $max = null,
        public array $fields = [],
        public ?Closure $optionsLoader = null,
    ) {
    }

    /**
     * Варианты select: статические или загруженные. Загрузчик вызывается на каждый вызов —
     * его зовут только форма и проверка при сохранении, по разу на поле.
     *
     * @return array<string, string>
     */
    public function options(): array
    {
        return $this->optionsLoader !== null ? array_map('strval', ($this->optionsLoader)()) : $this->options;
    }

    /**
     * Варианты select загружаются лениво (список из БД модуля).
     */
    public function hasLazyOptions(): bool
    {
        return $this->optionsLoader !== null;
    }

    /**
     * Поле из описания схемы.
     *
     * @param array<string, mixed> $definition
     * @param bool                 $inRepeater поле описывает колонку строки repeater
     * @throws InvalidArgumentException описание некорректно — ошибка автора типа, а не данных
     */
    public static function fromArray(string $name, array $definition, bool $inRepeater = false): self
    {
        if (preg_match(self::NAME_PATTERN, $name) !== 1) {
            throw new InvalidArgumentException("Block field name '{$name}' is invalid");
        }

        $kind = FieldKind::tryFrom((string)($definition['kind'] ?? ''));
        if ($kind === null) {
            throw new InvalidArgumentException("Block field '{$name}' has unknown kind");
        }
        if ($inRepeater && in_array($kind, self::NOT_IN_REPEATER, true)) {
            throw new InvalidArgumentException("Block field '{$name}': kind '{$kind->value}' is not allowed inside a repeater");
        }

        $loader = ($definition['options'] ?? null) instanceof Closure ? $definition['options'] : null;
        $options = $loader === null ? array_map('strval', (array)($definition['options'] ?? [])) : [];
        if ($kind === FieldKind::Select && $options === [] && $loader === null) {
            throw new InvalidArgumentException("Block field '{$name}': select requires options");
        }

        $fields = [];
        if ($kind === FieldKind::Repeater) {
            foreach ((array)($definition['fields'] ?? []) as $childName => $child) {
                $fields[] = self::fromArray((string)$childName, (array)$child, true);
            }
            if ($fields === []) {
                throw new InvalidArgumentException("Block field '{$name}': repeater requires fields");
            }
        }

        return new self(
            name: $name,
            kind: $kind,
            label: (string)($definition['label'] ?? $name),
            hint: (string)($definition['hint'] ?? ''),
            required: (bool)($definition['required'] ?? false),
            default: $definition['default'] ?? null,
            maxLength: isset($definition['maxLength']) ? (int)$definition['maxLength'] : null,
            options: $options,
            min: (int)($definition['min'] ?? 0),
            max: isset($definition['max']) ? (int)$definition['max'] : null,
            fields: $fields,
            optionsLoader: $loader,
        );
    }

    /**
     * Есть ли среди полей (включая колонки repeater) поле данного вида.
     *
     * @param list<Field> $fields
     */
    public static function containsKind(array $fields, FieldKind $kind): bool
    {
        foreach ($fields as $field) {
            if ($field->kind === $kind || self::containsKind($field->fields, $kind)) {
                return true;
            }
        }

        return false;
    }
}
