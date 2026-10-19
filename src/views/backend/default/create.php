<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Blocks\forms\backend\BlockForm;
use Besnovatyj\Contracts\block\BlockType;
use yii\web\View;

/* @var $this View */
/* @var $model BlockForm */
/* @var $areaOptions array<string,string> */
/* @var $hasCatalog bool */
/* @var $types array<string, BlockType> */

$this->title = 'Новый блок';
$this->params['breadcrumbs'][] = ['label' => 'Блоки темы', 'url' => ['backend/default/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="container-fluid">
    <p class="text-body-secondary">
        Сначала выберите место и тип блока. Содержимое заполняется после создания: у блока появится своя
        папка для файлов.
    </p>
    <?= $this->render('_form', [
        'model' => $model,
        'areaOptions' => $areaOptions,
        'hasCatalog' => $hasCatalog,
        'types' => $types,
    ]) ?>
</div>
