<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Blocks\entities\Block;
use Besnovatyj\Blocks\files\BlockFiles;
use Besnovatyj\Blocks\forms\backend\BlockDataForm;
use Besnovatyj\Blocks\forms\backend\BlockForm;
use Besnovatyj\Contracts\block\BlockType;
use Besnovatyj\DateTime\DateTimeWidget;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $model BlockForm */
/* @var $areaOptions array<string,string> */
/* @var $hasCatalog bool */
/* @var $types array<string, BlockType>|null создание: типы для выбранного места */
/* @var $block Block|null правка */
/* @var $type BlockType|null правка: тип блока (null — тип пропал из реестра) */
/* @var $dataForm BlockDataForm|null правка */
/* @var $dataEditable bool правка */
/* @var $linkTargets array<string, array<string, string>> правка */
/* @var $files BlockFiles|null правка */

$types ??= null;
$block ??= null;
$isCreate = $block === null;

$dateOptions = [
    'valueFormat' => BlockForm::DATE_FORMAT,
    'minuteStep' => 5,
    'clearable' => true,
    'options' => [
        'data-locale' => 'ru-RU',
        'data-week-starts-on' => '1',
    ],
];

// При создании список типов зависит от места: смена места перерисовывает его HTMX-запросом.
$areaHtmx = $isCreate ? [
    'hx-get' => Url::to(['types']),
    'hx-trigger' => 'change',
    'hx-target' => '#block-types',
    'hx-swap' => 'outerHTML',
    'hx-vals' => 'js:{area: event.target.value, selected: (document.querySelector("#block-types input:checked") || {}).value || ""}',
] : [];
?>
<?php $form = ActiveForm::begin(); ?>
<div class="card mb-3">
    <div class="card-header">Блок</div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-6">
                <?php if ($hasCatalog): ?>
                    <?= $form->field($model, 'area')->dropDownList($areaOptions, ['prompt' => 'Выберите место'] + $areaHtmx) ?>
                <?php else: ?>
                    <?= $form->field($model, 'area')->textInput(['maxlength' => true] + $areaHtmx)
                        ->hint('Идентификатор, который тема передаёт виджету места.') ?>
                <?php endif; ?>
            </div>
            <div class="col-lg-6">
                <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
            </div>
        </div>

        <?php if ($isCreate): ?>
            <div class="mb-3 <?= $model->hasErrors('type') ? 'has-error' : '' ?>">
                <label class="form-label"><?= Html::encode($model->getAttributeLabel('type')) ?></label>
                <?= $this->render('_types', [
                    'types' => $types ?? [],
                    'selected' => $model->type,
                    'inputName' => Html::getInputName($model, 'type'),
                ]) ?>
                <?php if ($model->hasErrors('type')): ?>
                    <div class="invalid-feedback d-block"><?= Html::encode($model->getFirstError('type')) ?></div>
                <?php endif; ?>
                <div class="form-text"><?= Html::encode($model->getAttributeHint('type')) ?></div>
            </div>
        <?php else: ?>
            <p class="mb-3">
                <span class="text-body-secondary">Тип:</span>
                <?php if ($type !== null): ?>
                    <?= Html::encode($type->label) ?> <code><?= Html::encode($type->id) ?></code>
                <?php else: ?>
                    <span class="badge text-bg-warning">Тип недоступен</span> <code><?= Html::encode($block->type) ?></code>
                <?php endif; ?>
            </p>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-3">
                <?= $form->field($model, 'status')->dropDownList(Block::statusList()) ?>
            </div>
            <div class="col-lg-3">
                <?= $form->field($model, 'show_from')->widget(DateTimeWidget::class, $dateOptions) ?>
            </div>
            <div class="col-lg-3">
                <?= $form->field($model, 'show_to')->widget(DateTimeWidget::class, $dateOptions) ?>
            </div>
            <div class="col-lg-3">
                <?= $form->field($model, 'sort_order')->textInput() ?>
            </div>
        </div>
    </div>
</div>

<?php if (!$isCreate): ?>
    <?= $this->render('_data', [
        'block' => $block,
        'type' => $type,
        'dataForm' => $dataForm,
        'dataEditable' => $dataEditable,
        'linkTargets' => $linkTargets,
        'files' => $files,
    ]) ?>
<?php endif; ?>

<div class="d-grid mb-3">
    <?= Html::submitButton($isCreate ? 'Создать и перейти к содержимому' : 'Сохранить', ['class' => 'btn btn-success']) ?>
</div>
<?php ActiveForm::end(); ?>
