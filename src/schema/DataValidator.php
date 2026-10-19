<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\schema;

use Besnovatyj\Blocks\files\BlockFiles;

/**
 * Проверка данных блока, уже приведённых к схеме ({@see DataNormalizer}).
 *
 * Ошибки возвращаются картой `путь => сообщение`, путь — через точку: `heading`, `cards.0.image`.
 * Форма раскладывает их по полям.
 *
 * Проверки, которые нельзя доверить шаблону:
 * - URL ссылки — только относительный, `#`, http(s), mailto и tel. `javascript:` и прочие схемы
 *   отсекаются: ссылку на сайте рисует шаблон, и он не должен стать вектором XSS;
 * - файл изображения обязан лежать в папке блока и иметь расширение изображения.
 *
 * Обращение к диску (проверка файла) идёт только здесь, то есть только при сохранении в админке.
 */
final class DataValidator
{
    /** Допустимые начала URL свободной ссылки. */
    private const string URL_PATTERN = '~^(/(?!/)|#|https?://|mailto:|tel:)~i';

    public function __construct(private readonly BlockFiles $files)
    {
    }

    /**
     * @param array<string, mixed> $data нормализованные данные
     * @return array<string, string> путь поля => сообщение
     */
    public function validate(Schema $schema, array $data, int $blockId): array
    {
        $errors = [];
        $this->fields($schema->fields, $data, $blockId, '', $errors);

        return $errors;
    }

    /**
     * @param list<Field>           $fields
     * @param array<string, mixed>  $data
     * @param array<string, string> $errors
     */
    private function fields(array $fields, array $data, int $blockId, string $prefix, array &$errors): void
    {
        foreach ($fields as $field) {
            $path = $prefix . $field->name;
            $error = $this->check($field, $data[$field->name] ?? null, $blockId, $path, $errors);
            if ($error !== null) {
                $errors[$path] = $error;
            }
        }
    }

    /**
     * @param array<string, string> $errors для ошибок вложенных строк repeater
     * @return string|null ошибка самого поля
     */
    private function check(Field $field, mixed $value, int $blockId, string $path, array &$errors): ?string
    {
        switch ($field->kind) {
            case FieldKind::Text:
            case FieldKind::Textarea:
            case FieldKind::Richtext:
            case FieldKind::Code:
                $value = (string)$value;
                if ($field->required && trim(strip_tags($value)) === '' && !str_contains($value, '<img')) {
                    return 'Заполните поле.';
                }
                if ($field->maxLength !== null && mb_strlen($value) > $field->maxLength) {
                    return "Не длиннее {$field->maxLength} символов.";
                }
                return null;

            case FieldKind::Image:
                $file = (string)($value['file'] ?? '');
                if ($file === '') {
                    return $field->required ? 'Выберите изображение.' : null;
                }
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($extension, BlockFiles::IMAGE_EXTENSIONS, true)) {
                    return 'Это не изображение.';
                }
                if (!$this->files->exists($blockId, $file)) {
                    return 'Файла нет в папке блока.';
                }
                return null;

            case FieldKind::Link:
                $url = (string)($value['url'] ?? '');
                if (($value['route'] ?? '') !== '') {
                    return null;
                }
                if ($url === '') {
                    return $field->required ? 'Укажите ссылку.' : null;
                }
                return preg_match(self::URL_PATTERN, $url) === 1
                    ? null
                    : 'Ссылка должна начинаться с /, #, http://, https://, mailto: или tel:.';

            case FieldKind::Repeater:
                $rows = is_array($value) ? $value : [];
                foreach ($rows as $index => $row) {
                    $this->fields($field->fields, $row, $blockId, "{$path}.{$index}.", $errors);
                }
                $min = max($field->min, $field->required ? 1 : 0);
                if (count($rows) < $min) {
                    return "Нужно строк: не меньше {$min}.";
                }
                if ($field->max !== null && count($rows) > $field->max) {
                    return "Нужно строк: не больше {$field->max}.";
                }
                return null;

            case FieldKind::Select:
                // Статический список нормализатор уже привёл к допустимому значению. Ленивый —
                // нормализатор не загружает (он работает и при выводе), проверка здесь.
                if (!$field->hasLazyOptions()) {
                    return null;
                }
                $value = (string)$value;
                if ($value === '') {
                    return $field->required ? 'Выберите значение.' : null;
                }
                return array_key_exists($value, $field->options()) ? null : 'Выберите значение из списка.';

            case FieldKind::Checkbox:
                // Значение уже приведено нормализатором к допустимому.
                return null;
        }

        return null;
    }
}
