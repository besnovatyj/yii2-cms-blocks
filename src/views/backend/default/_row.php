<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Blocks\files\BlockFiles;
use Besnovatyj\Blocks\forms\backend\BlockDataForm;
use Besnovatyj\Blocks\schema\Field;
use yii\helpers\Html;
use yii\web\View;

/**
 * Строка repeater. Отдаётся и в составе формы, и отдельно HTMX-запросом «Добавить строку».
 *
 * У строк из БД ключ — индекс (по нему ищутся ошибки, `cards.0.image`), у новых — случайный: он лишь
 * разводит имена input'ов, порядок строк задаёт DOM.
 */

/* @var $this View */
/* @var $field Field поле-repeater */
/* @var $key string */
/* @var $row array<string, mixed> */
/* @var $path string путь поля-repeater */
/* @var $blockId int */
/* @var $dataForm BlockDataForm|null */
/* @var $linkTargets array<string, array<string, string>> */
/* @var $files BlockFiles */

$formName = $dataForm?->formName() ?? 'BlockData';
?>
<div class="card mb-2" data-blocks-row>
    <div class="card-body pb-0">
        <div class="d-flex justify-content-end gap-1 mb-2">
            <?= Html::button('<i class="bi bi-arrow-up"></i>', ['class' => 'btn btn-sm btn-outline-secondary', 'title' => 'Выше', 'data-blocks-action' => 'up']) ?>
            <?= Html::button('<i class="bi bi-arrow-down"></i>', ['class' => 'btn btn-sm btn-outline-secondary', 'title' => 'Ниже', 'data-blocks-action' => 'down']) ?>
            <?= Html::button('<i class="bi bi-trash"></i>', ['class' => 'btn btn-sm btn-outline-danger', 'title' => 'Удалить строку', 'data-blocks-action' => 'remove']) ?>
        </div>
        <?php foreach ($field->fields as $child): ?>
            <?= $this->render('_field', [
                'field' => $child,
                'value' => $row[$child->name],
                'name' => $formName . '[' . $field->name . '][' . $key . '][' . $child->name . ']',
                'path' => $path . '.' . $key . '.' . $child->name,
                'blockId' => $blockId,
                'dataForm' => $dataForm,
                'linkTargets' => $linkTargets,
                'files' => $files,
            ]) ?>
        <?php endforeach; ?>
    </div>
</div>
