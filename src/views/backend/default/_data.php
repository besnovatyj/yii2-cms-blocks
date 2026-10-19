<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Blocks\entities\Block;
use Besnovatyj\Blocks\files\BlockFiles;
use Besnovatyj\Blocks\forms\backend\BlockDataForm;
use Besnovatyj\Blocks\schema\FieldKind;
use Besnovatyj\Contracts\block\BlockType;
use yii\helpers\Html;
use yii\web\View;

/**
 * Содержимое блока: поля по схеме типа.
 *
 * Без права на код виджета форма данных не выводится вовсе (сервер её и не примет), а код показывается
 * текстом. Код чужого виджета в админке никогда не рендерится как HTML.
 */

/* @var $this View */
/* @var $block Block */
/* @var $type BlockType|null */
/* @var $dataForm BlockDataForm|null */
/* @var $dataEditable bool */
/* @var $linkTargets array<string, array<string, string>> */
/* @var $files BlockFiles */

$formName = $dataForm?->formName() ?? 'BlockData';

// Строки repeater: перестановка и удаление — перестановкой элементов в DOM. Порядок полей в POST и
// есть порядок строк ({@see BlockDataForm}), переиндексация не нужна. Очистка поля изображения —
// здесь же: поле выбора файла только для чтения.
$this->registerJs(<<<'JS'
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-blocks-action]');
    if (!btn) return;
    const action = btn.dataset.blocksAction;
    if (action === 'clear') {
        const input = document.getElementById(btn.dataset.blocksTarget);
        if (input) { input.value = ''; input.dispatchEvent(new Event('change', {bubbles: true})); }
        return;
    }
    const row = btn.closest('[data-blocks-row]');
    if (!row) return;
    if (action === 'remove' && confirm('Удалить строку?')) row.remove();
    if (action === 'up' && row.previousElementSibling) row.parentNode.insertBefore(row, row.previousElementSibling);
    if (action === 'down' && row.nextElementSibling) row.parentNode.insertBefore(row.nextElementSibling, row);
});
JS, View::POS_READY, 'blocks-data-form');
?>
<div class="card mb-3">
    <div class="card-header">
        Содержимое<?= $type !== null ? ': ' . Html::encode($type->label) : '' ?>
    </div>
    <div class="card-body">
        <?php if ($type === null || $dataForm === null): ?>
            <div class="alert alert-warning mb-0">
                Тип блока <code><?= Html::encode($block->type) ?></code> сейчас недоступен: его объявляла
                другая тема или удалённый модуль. Блок на сайт не выводится, данные сохранены как есть.
            </div>
        <?php elseif (!$dataEditable): ?>
            <div class="alert alert-info">
                Код чужого виджета правят только пользователи с отдельным разрешением. Место, название,
                статус и сроки показа менять можно.
            </div>
            <?php foreach ($dataForm->schema->fields as $field): ?>
                <?php if ($field->kind === FieldKind::Code): ?>
                    <label class="form-label"><?= Html::encode($field->label) ?></label>
                    <pre class="border rounded p-2 bg-body-tertiary"><code><?= Html::encode((string)$dataForm->data[$field->name]) ?></code></pre>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <?php if ($type->hint !== ''): ?>
                <p class="text-body-secondary"><?= Html::encode($type->hint) ?></p>
            <?php endif; ?>
            <?= Html::hiddenInput($formName . '[' . BlockDataForm::PRESENCE_KEY . ']', '1') ?>
            <?php foreach ($dataForm->schema->fields as $field): ?>
                <?= $this->render('_field', [
                    'field' => $field,
                    'value' => $dataForm->data[$field->name],
                    'name' => $formName . '[' . $field->name . ']',
                    'path' => $field->name,
                    'blockId' => $block->id,
                    'dataForm' => $dataForm,
                    'linkTargets' => $linkTargets,
                    'files' => $files,
                ]) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
