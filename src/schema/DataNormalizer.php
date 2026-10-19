<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\schema;

use Besnovatyj\Blocks\files\BlockFiles;

/**
 * Приведение данных блока к схеме типа.
 *
 * Одна функция на два случая: данные из формы и данные из БД. Схема со временем меняется, поэтому
 * отсутствующее поле получает значение по умолчанию, а лишнее выбрасывается. Версий схем нет: шаблон
 * типа всегда получает полный набор ключей своей схемы и не проверяет `isset`.
 *
 * Значения по видам ({@see FieldKind}):
 * - text, textarea, richtext, code — строка;
 * - select — ключ из `options` (чужое значение заменяется значением по умолчанию); у ленивого списка
 *   (`options` — замыкание) строка как есть, её проверяет {@see DataValidator};
 * - checkbox — bool;
 * - image — `['file' => string, 'alt' => string]`, `file` относительно папки блока ({@see BlockFiles});
 * - link — `['url' => string, 'route' => string, 'params' => array<string,string>, 'blank' => bool]`,
 *   `blank` — открывать в новой вкладке;
 * - repeater — список строк, каждая приведена к полям строки. Порядок строк — порядок во входных
 *   данных (в форме — порядок в DOM), ключи отбрасываются.
 *
 * Из формы ссылка приходит как `['url' => '…', 'target' => 'route|param|slug']` — выбор цели из
 * списка; см. {@see self::encodeTarget()}.
 */
final class DataNormalizer
{
    public function __construct(private readonly BlockFiles $files)
    {
    }

    /**
     * @param array<string, mixed> $input
     * @param int|null             $blockId id блока (для путей файлов); null — блок ещё не сохранён
     * @return array<string, mixed>
     */
    public function normalize(Schema $schema, array $input, ?int $blockId): array
    {
        return $this->fields($schema->fields, $input, $blockId);
    }

    /**
     * Значение цели ссылки для списка в форме.
     *
     * @param array<string, string> $params
     */
    public static function encodeTarget(string $route, array $params): string
    {
        $param = (string)array_key_first($params);

        return $route === '' ? '' : $route . '|' . $param . '|' . ($params[$param] ?? '');
    }

    /**
     * @param list<Field>          $fields
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function fields(array $fields, array $input, ?int $blockId): array
    {
        $data = [];
        foreach ($fields as $field) {
            $data[$field->name] = $this->value($field, $input[$field->name] ?? null, $blockId);
        }

        return $data;
    }

    private function value(Field $field, mixed $raw, ?int $blockId): mixed
    {
        return match ($field->kind) {
            FieldKind::Text, FieldKind::Textarea, FieldKind::Richtext, FieldKind::Code
                => is_scalar($raw) ? (string)$raw : (string)($field->default ?? ''),
            FieldKind::Select => $this->select($field, $raw),
            FieldKind::Checkbox => $raw === null ? (bool)$field->default : filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            FieldKind::Image => $this->image($raw, $blockId),
            FieldKind::Link => $this->link($raw),
            FieldKind::Repeater => $this->rows($field, $raw, $blockId),
        };
    }

    private function select(Field $field, mixed $raw): string
    {
        $value = is_scalar($raw) ? (string)$raw : '';

        // Ленивый список здесь не загружается: нормализатор работает и при выводе блока.
        // Значение проверяет DataValidator при сохранении.
        if ($field->hasLazyOptions()) {
            return $raw === null ? (string)($field->default ?? '') : $value;
        }

        if (array_key_exists($value, $field->options)) {
            return $value;
        }

        $default = (string)($field->default ?? '');

        return array_key_exists($default, $field->options) ? $default : (string)array_key_first($field->options);
    }

    /**
     * @return array{file:string, alt:string}
     */
    private function image(mixed $raw, ?int $blockId): array
    {
        $raw = is_array($raw) ? $raw : [];
        $file = is_scalar($raw['file'] ?? null) ? (string)$raw['file'] : '';

        return [
            'file' => $blockId === null ? '' : ($this->files->relative($blockId, $file) ?? ''),
            'alt' => is_scalar($raw['alt'] ?? null) ? trim((string)$raw['alt']) : '',
        ];
    }

    /**
     * @return array{url:string, route:string, params:array<string,string>, blank:bool}
     */
    private function link(mixed $raw): array
    {
        $raw = is_array($raw) ? $raw : [];
        $url = is_scalar($raw['url'] ?? null) ? trim((string)$raw['url']) : '';
        $route = is_scalar($raw['route'] ?? null) ? (string)$raw['route'] : '';
        $params = is_array($raw['params'] ?? null) ? array_map('strval', array_filter($raw['params'], 'is_scalar')) : [];
        // Ссылки, сохранённые до появления флажка, его не несут — открываются как раньше, на месте.
        $blank = isset($raw['blank']) && filter_var($raw['blank'], FILTER_VALIDATE_BOOLEAN);

        if (array_key_exists('target', $raw)) {
            [$route, $params] = $this->decodeTarget(is_scalar($raw['target']) ? (string)$raw['target'] : '');
        }

        // Выбрана цель — она главнее: URL по маршруту переживает смену адреса раздела.
        return $route !== ''
            ? ['url' => '', 'route' => $route, 'params' => $params, 'blank' => $blank]
            : ['url' => $url, 'route' => '', 'params' => [], 'blank' => $blank];
    }

    /**
     * @return array{0:string, 1:array<string,string>}
     */
    private function decodeTarget(string $target): array
    {
        $parts = explode('|', $target, 3);
        if (count($parts) !== 3 || $parts[0] === '') {
            return ['', []];
        }

        return [$parts[0], $parts[1] === '' ? [] : [$parts[1] => $parts[2]]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(Field $field, mixed $raw, ?int $blockId): array
    {
        $rows = [];
        foreach (is_array($raw) ? $raw : [] as $row) {
            if (is_array($row)) {
                $rows[] = $this->fields($field->fields, $row, $blockId);
            }
        }

        return $rows;
    }
}
