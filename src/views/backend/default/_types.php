<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Contracts\block\BlockType;
use yii\helpers\Html;
use yii\web\View;

/**
 * Выбор типа блока при создании: карточки с названием и подсказкой. Перерисовывается целиком
 * (HTMX, outerHTML) при смене места, поэтому корневой id постоянный.
 */

/* @var $this View */
/* @var $types array<string, BlockType> */
/* @var $selected string */
/* @var $inputName string */
?>
<div id="block-types" class="row g-2">
    <?php if ($types === []): ?>
        <div class="col-12 text-body-secondary">Выберите место: список типов зависит от него.</div>
    <?php endif; ?>
    <?php foreach ($types as $id => $type): ?>
        <div class="col-md-6 col-xl-4">
            <label class="card h-100" style="cursor: pointer;">
                <span class="card-body d-flex gap-2">
                    <?= Html::radio($inputName, $selected === $id, ['value' => $id, 'class' => 'form-check-input flex-shrink-0']) ?>
                    <span>
                        <strong><?= Html::encode($type->label) ?></strong>
                        <code class="small ms-1"><?= Html::encode($id) ?></code>
                        <?php if ($type->hint !== ''): ?>
                            <span class="d-block small text-body-secondary"><?= Html::encode($type->hint) ?></span>
                        <?php endif; ?>
                    </span>
                </span>
            </label>
        </div>
    <?php endforeach; ?>
</div>
