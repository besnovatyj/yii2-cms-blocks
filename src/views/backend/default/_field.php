<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Blocks\files\BlockFiles;
use Besnovatyj\Blocks\forms\backend\BlockDataForm;
use Besnovatyj\Blocks\schema\DataNormalizer;
use Besnovatyj\Blocks\schema\Field;
use Besnovatyj\Blocks\schema\FieldKind;
use Besnovatyj\Editor\EditorWidget;
use Besnovatyj\FileManager\ExplorerWidget;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/**
 * Одно поле формы данных блока по его виду ({@see FieldKind}).
 *
 * Имена input'ов собираются из `$name` (`BlockData[heading]`, `BlockData[cards][n1a2][image]`), ошибки
 * ищутся по `$path` (`heading`, `cards.0.image`). id элементов — хэш имени: так они уникальны и у
 * строк repeater, добавленных HTMX-запросом.
 */

/* @var $this View */
/* @var $field Field */
/* @var $value mixed значение по схеме ({@see DataNormalizer}) */
/* @var $name string */
/* @var $path string */
/* @var $blockId int */
/* @var $dataForm BlockDataForm|null null — новая строка repeater, ошибок нет */
/* @var $linkTargets array<string, array<string, string>> */
/* @var $files BlockFiles */

$id = 'blk-' . substr(md5($name), 0, 12);
$error = $dataForm?->fieldError($path);
$inputClass = 'form-control' . ($error !== null ? ' is-invalid' : '');
$label = Html::label(Html::encode($field->label) . ($field->required ? ' <span class="text-danger">*</span>' : ''), $id, ['class' => 'form-label']);
?>
<div class="mb-3">
    <?php switch ($field->kind):
        case FieldKind::Text: ?>
            <?= $label ?>
            <?= Html::textInput($name, (string)$value, ['id' => $id, 'class' => $inputClass, 'maxlength' => $field->maxLength]) ?>
            <?php break; ?>

        <?php case FieldKind::Textarea: ?>
            <?= $label ?>
            <?= Html::textarea($name, (string)$value, ['id' => $id, 'class' => $inputClass, 'rows' => 4, 'maxlength' => $field->maxLength]) ?>
            <?php break; ?>

        <?php case FieldKind::Code: ?>
            <?= $label ?>
            <?= Html::textarea($name, (string)$value, ['id' => $id, 'class' => $inputClass . ' font-monospace', 'rows' => 12, 'spellcheck' => 'false']) ?>
            <?php break; ?>

        <?php case FieldKind::Richtext: ?>
            <?= $label ?>
            <?php // Пустые строчные элементы в начинке блоков — норма: иконки шрифтов
                  // (`<span class="fa-…"></span>`) и декоративные span тем. Jodit по умолчанию
                  // их вычищает (`cleanHTML.removeEmptyElements`), поэтому здесь чистка выключена.
                  // Только для этого поля: в контенте страниц пустые теги — мусор, там она нужна.
                  // Остальные опции `cleanHTML` Jodit берёт из своих дефолтов (глубокое слияние). ?>
            <?= EditorWidget::widget([
                'name' => $name,
                'value' => (string)$value,
                'options' => ['id' => $id],
                'height' => 300,
                'enableSnippets' => true,
                'fmDefaultPath' => $files->virtualDir($blockId),
                'engineConfig' => [
                    'jodit' => [
                        'config' => [
                            'cleanHTML' => ['removeEmptyElements' => false],
                        ],
                    ],
                ],
            ]) ?>
            <?php break; ?>

        <?php case FieldKind::Select: ?>
            <?= $label ?>
            <?= Html::dropDownList($name, (string)$value, $field->options, ['id' => $id, 'class' => 'form-select' . ($error !== null ? ' is-invalid' : '')]) ?>
            <?php break; ?>

        <?php case FieldKind::Checkbox: ?>
            <div class="form-check">
                <?= Html::checkbox($name, (bool)$value, ['id' => $id, 'class' => 'form-check-input', 'value' => '1', 'uncheck' => '0']) ?>
                <?= Html::label(Html::encode($field->label), $id, ['class' => 'form-check-label']) ?>
            </div>
            <?php break; ?>

        <?php case FieldKind::Image: ?>
            <?= $label ?>
            <div class="row g-2">
                <div class="col-md-7">
                    <div class="d-flex gap-2">
                        <div class="flex-grow-1">
                            <?= ExplorerWidget::widget([
                                'name' => $name . '[file]',
                                'value' => $files->virtualPath($blockId, (string)$value['file']),
                                'options' => ['id' => $id, 'class' => $inputClass],
                                'scope' => $files->virtualDir($blockId),
                                'startPath' => $files->virtualDir($blockId),
                                'returnAttribute' => 'path',
                                'pickFilter' => 'image',
                                'triggerLabel' => 'Выбрать',
                            ]) ?>
                        </div>
                        <?= Html::button('×', [
                            'class' => 'btn btn-outline-secondary',
                            'title' => 'Убрать изображение',
                            'data-blocks-action' => 'clear',
                            'data-blocks-target' => $id,
                        ]) ?>
                    </div>
                </div>
                <div class="col-md-5">
                    <?= Html::textInput($name . '[alt]', (string)$value['alt'], [
                        'class' => 'form-control',
                        'placeholder' => 'Что на изображении (alt)',
                        'aria-label' => 'Описание изображения',
                    ]) ?>
                </div>
            </div>
            <?php if ($value['file'] !== ''): ?>
                <img src="<?= Html::encode($files->url($blockId, (string)$value['file'])) ?>" alt=""
                     class="mt-2 rounded border" style="max-height: 80px;">
            <?php endif; ?>
            <?php break; ?>

        <?php case FieldKind::Link: ?>
            <?= $label ?>
            <div class="row g-2">
                <?php if ($linkTargets !== []): ?>
                    <div class="col-md-6">
                        <?= Html::dropDownList(
                            $name . '[target]',
                            DataNormalizer::encodeTarget((string)$value['route'], (array)$value['params']),
                            $linkTargets,
                            ['class' => 'form-select', 'prompt' => '— свой адрес —', 'aria-label' => 'Раздел сайта'],
                        ) ?>
                    </div>
                <?php endif; ?>
                <div class="<?= $linkTargets !== [] ? 'col-md-6' : 'col-12' ?>">
                    <?= Html::textInput($name . '[url]', (string)$value['url'], [
                        'id' => $id,
                        'class' => $inputClass,
                        'placeholder' => 'https://… или /путь',
                    ]) ?>
                </div>
            </div>
            <?php if ($linkTargets !== []): ?>
                <div class="form-text">Раздел сайта главнее своего адреса: ссылка на раздел не ломается при смене его адреса.</div>
            <?php endif; ?>
            <?php break; ?>

        <?php case FieldKind::Repeater: ?>
            <?php $rowsId = $id . '-rows'; ?>
            <?= $label ?>
            <div id="<?= $rowsId ?>">
                <?php foreach ((array)$value as $index => $row): ?>
                    <?= $this->render('_row', [
                        'field' => $field,
                        'key' => (string)$index,
                        'row' => $row,
                        'path' => $path,
                        'blockId' => $blockId,
                        'dataForm' => $dataForm,
                        'linkTargets' => $linkTargets,
                        'files' => $files,
                    ]) ?>
                <?php endforeach; ?>
            </div>
            <?= Html::button('<i class="bi bi-plus-lg me-1"></i>Добавить строку', [
                'class' => 'btn btn-sm btn-outline-secondary',
                'hx-get' => Url::to(['row', 'id' => $blockId, 'field' => $field->name]),
                'hx-target' => '#' . $rowsId,
                'hx-swap' => 'beforeend',
            ]) ?>
            <?php if ($field->min > 0 || $field->max !== null): ?>
                <span class="form-text ms-2">
                    Строк: <?= $field->min > 0 ? 'от ' . $field->min : '' ?> <?= $field->max !== null ? 'до ' . $field->max : '' ?>
                </span>
            <?php endif; ?>
            <?php break; ?>
    <?php endswitch; ?>

    <?php if ($error !== null): ?>
        <div class="invalid-feedback d-block"><?= Html::encode($error) ?></div>
    <?php endif; ?>
    <?php if ($field->hint !== ''): ?>
        <div class="form-text"><?= Html::encode($field->hint) ?></div>
    <?php endif; ?>
</div>
