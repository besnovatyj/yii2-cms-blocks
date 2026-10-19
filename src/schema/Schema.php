<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\schema;

use InvalidArgumentException;

/**
 * Схема типа блока: упорядоченный список полей.
 *
 * Строится из {@see \Besnovatyj\Contracts\block\BlockType::$fields}. Некорректное описание — ошибка
 * автора типа, поэтому разбор бросает исключение, а не молча пропускает поле.
 */
final readonly class Schema
{
    /**
     * @param list<Field> $fields
     */
    public function __construct(public array $fields)
    {
    }

    /**
     * @param array<string, array<string, mixed>> $definition `имя поля => описание`
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $definition): self
    {
        $fields = [];
        foreach ($definition as $name => $field) {
            $fields[] = Field::fromArray((string)$name, (array)$field);
        }

        return new self($fields);
    }

    /**
     * Есть ли в схеме поле данного вида (включая колонки repeater).
     */
    public function contains(FieldKind $kind): bool
    {
        return Field::containsKind($this->fields, $kind);
    }
}
