<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Blocks\entities\Block;
use Besnovatyj\Blocks\files\BlockFiles;
use Besnovatyj\Blocks\forms\backend\BlockDataForm;
use Besnovatyj\Blocks\forms\backend\BlockForm;
use Besnovatyj\Contracts\block\BlockType;
use yii\web\View;

/* @var $this View */
/* @var $model BlockForm */
/* @var $block Block */
/* @var $type BlockType|null */
/* @var $dataForm BlockDataForm|null */
/* @var $dataEditable bool */
/* @var $areaOptions array<string,string> */
/* @var $hasCatalog bool */
/* @var $declared bool */
/* @var $linkTargets array<string, array<string, string>> */
/* @var $files BlockFiles */

$this->title = 'Блок: ' . $block->title;
$this->params['breadcrumbs'][] = ['label' => 'Блоки темы', 'url' => ['backend/default/index']];
$this->params['breadcrumbs'][] = 'Редактирование';
?>
<div class="container-fluid">
    <?php if (!$declared): ?>
        <div class="alert alert-warning">
            Активная тема не объявляет место этого блока, поэтому на сайт он не выводится.
            Перенесите блок в другое место или оставьте до возврата темы, которая это место знает.
        </div>
    <?php endif; ?>
    <?= $this->render('_form', [
        'model' => $model,
        'areaOptions' => $areaOptions,
        'hasCatalog' => $hasCatalog,
        'block' => $block,
        'type' => $type,
        'dataForm' => $dataForm,
        'dataEditable' => $dataEditable,
        'linkTargets' => $linkTargets,
        'files' => $files,
    ]) ?>
</div>
