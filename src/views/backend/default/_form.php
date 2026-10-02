<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Blocks\entities\Block;
use Besnovatyj\Blocks\forms\backend\BlockForm;
use Besnovatyj\DateTime\DateTimeWidget;
use Besnovatyj\Editor\EditorWidget;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\web\View;

/* @var $this View */
/* @var $model BlockForm */
/* @var $areaOptions array<string,string> */
/* @var $hasCatalog bool */

$dateOptions = [
    'valueFormat' => BlockForm::DATE_FORMAT,
    'minuteStep' => 5,
    'clearable' => true,
    'options' => [
        'data-locale' => 'ru-RU',
        'data-week-starts-on' => '1',
    ],
];
?>
<?php $form = ActiveForm::begin(); ?>
<div class="card">
    <div class="card-header">Блок</div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-6">
                <?php if ($hasCatalog): ?>
                    <?= $form->field($model, 'area')->dropDownList($areaOptions, ['prompt' => 'Выберите место']) ?>
                <?php else: ?>
                    <?= $form->field($model, 'area')->textInput(['maxlength' => true])
                        ->hint('Идентификатор, который тема передаёт виджету места.') ?>
                <?php endif; ?>
            </div>
            <div class="col-lg-6">
                <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
            </div>
        </div>

        <?php // Пустые строчные элементы в начинке блоков — норма: иконки шрифтов
              // (`<span class="fa-…"></span>`) и декоративные span тем. Jodit по умолчанию
              // их вычищает (`cleanHTML.removeEmptyElements`), поэтому здесь чистка выключена.
              // Только для этого поля: в контенте страниц пустые теги — мусор, там она нужна.
              // Остальные опции `cleanHTML` Jodit берёт из своих дефолтов (глубокое слияние). ?>
        <?= $form->field($model, 'content')->widget(EditorWidget::class, [
            'height' => 300,
            'enableSnippets' => true,
            'engineConfig' => [
                'jodit' => [
                    'config' => [
                        'cleanHTML' => ['removeEmptyElements' => false],
                    ],
                ],
            ],
        ]) ?>

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
    <div class="card-footer">
        <div class="d-grid">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
        </div>
    </div>
</div>
<?php ActiveForm::end(); ?>
