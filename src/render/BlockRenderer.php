<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\render;

use Besnovatyj\Blocks\files\BlockFiles;
use Besnovatyj\Blocks\schema\DataNormalizer;
use Besnovatyj\Blocks\schema\Schema;
use Besnovatyj\Blocks\types\BlockTypeRegistry;
use Besnovatyj\Contracts\block\BlockDataProvider;
use Throwable;
use Yii;

/**
 * Вывод одного блока шаблоном его типа.
 *
 * Тип → данные приводятся к схеме → (поставщик данных) → `View::renderFile()`. Через `renderFile` на
 * шаблон действует overlay темы, поэтому шаблоны модулей тема перекрывает без участия этого класса.
 *
 * Шаблон получает переменные:
 * - `$data` — данные блока по схеме типа ({@see DataNormalizer});
 * - `$block` — {@see BlockViewContext}: id, тип, место и помощники вывода;
 * - всё, что вернул поставщик данных типа (не перекрывает `$data` и `$block`).
 *
 * Сломанный блок не роняет страницу: тип пропал — пустота и предупреждение в лог; ошибка в шаблоне
 * или в поставщике — пустота и ошибка в лог. В режиме отладки ошибка шаблона пробрасывается, чтобы
 * автор типа её увидел.
 */
final class BlockRenderer
{
    public function __construct(
        private readonly BlockTypeRegistry $types,
        private readonly DataNormalizer $normalizer,
        private readonly BlockFiles $files,
    ) {
    }

    /**
     * @param int                  $id   id блока
     * @param string               $type полный id типа
     * @param string               $area место блока
     * @param array<string, mixed> $data данные блока из БД
     * @throws Throwable только в режиме отладки
     */
    public function render(int $id, string $type, string $area, array $data): string
    {
        $blockType = $this->types->get($type);
        if ($blockType === null) {
            Yii::warning("Block #{$id}: unknown block type '{$type}', skipped", __METHOD__);
            return '';
        }

        try {
            $data = $this->normalizer->normalize(Schema::fromArray($blockType->fields), $data, $id);

            $extra = [];
            if ($blockType->dataProvider !== null) {
                $provider = Yii::$container->get($blockType->dataProvider);
                if ($provider instanceof BlockDataProvider) {
                    $extra = $provider->provide($data);
                }
            }

            return Yii::$app->getView()->renderFile($blockType->view, array_merge($extra, [
                'data' => $data,
                'block' => new BlockViewContext($id, $type, $area, $this->files),
            ]));
        } catch (Throwable $e) {
            if (YII_DEBUG) {
                throw $e;
            }
            Yii::error("Block #{$id} ({$type}) failed to render: {$e->getMessage()}", __METHOD__);
            return '';
        }
    }
}
