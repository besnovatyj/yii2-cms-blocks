<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\Blocks\render\BlockViewContext;

/**
 * Встроенный тип `embed`: код чужого виджета как есть, без шорткодов и обёртки.
 *
 * Выводится только на сайте. В админке этот код никогда не рендерится, только показывается текстом.
 */

/** @var array{code:string} $data */
/** @var BlockViewContext $block */
?>
<?= $data['code'] ?>
