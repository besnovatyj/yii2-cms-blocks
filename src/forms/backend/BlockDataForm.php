<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\forms\backend;

use Besnovatyj\Blocks\schema\DataNormalizer;
use Besnovatyj\Blocks\schema\DataValidator;
use Besnovatyj\Blocks\schema\Schema;
use Besnovatyj\Forms\BaseForm;

/**
 * Форма данных блока по схеме его типа.
 *
 * Поля формы не объявлены свойствами: их задаёт схема типа. Весь ввод лежит в {@see $data} под именем
 * формы `BlockData`, приводится к схеме ({@see DataNormalizer}) при загрузке и проверяется
 * ({@see DataValidator}) при валидации. Ошибки — по путям полей (`cards.0.image`), см. {@see fieldError()}.
 *
 * Строки repeater приходят под случайными ключами, а порядок строк — это порядок полей в POST, то есть
 * в DOM. Поэтому перестановка строк в форме — это просто перестановка элементов, без переиндексации.
 */
class BlockDataForm extends BaseForm
{
    /**
     * Служебное поле формы: есть всегда, чтобы форма считалась отправленной, даже если в ней не
     * осталось ни одного поля (удалены все строки единственного repeater).
     */
    public const string PRESENCE_KEY = '__present';

    /** @var array<string, mixed> данные по схеме типа */
    public array $data = [];

    /** @var array<string, string> путь поля => ошибка */
    private array $fieldErrors = [];

    /**
     * @param array<string, mixed> $data текущие данные блока (из БД)
     */
    public function __construct(
        public readonly Schema $schema,
        public readonly int $blockId,
        array $data,
        private readonly DataNormalizer $normalizer,
        private readonly DataValidator $validator,
        array $config = [],
    ) {
        $this->data = $this->normalizer->normalize($schema, $data, $blockId);
        parent::__construct($config);
    }

    public function formName(): string
    {
        return 'BlockData';
    }

    /**
     * Ввод формы целиком: правила по атрибутам здесь не работают, поля задаёт схема.
     */
    public function load($data, $formName = null): bool
    {
        $input = $data[$this->formName()] ?? null;
        if (!is_array($input)) {
            return false;
        }

        $this->data = $this->normalizer->normalize($this->schema, $input, $this->blockId);
        return true;
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        if ($clearErrors) {
            $this->clearErrors();
        }

        $this->fieldErrors = $this->validator->validate($this->schema, $this->data, $this->blockId);
        foreach ($this->fieldErrors as $message) {
            $this->addError('data', $message);
        }

        return $this->fieldErrors === [];
    }

    /**
     * Ошибка поля по пути (`heading`, `cards.0.image`); null — ошибки нет.
     */
    public function fieldError(string $path): ?string
    {
        return $this->fieldErrors[$path] ?? null;
    }
}
