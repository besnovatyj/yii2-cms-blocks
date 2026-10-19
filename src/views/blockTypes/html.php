<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\Blocks\render\BlockViewContext;

/**
 * Встроенный тип `html`: HTML из редактора с развёрнутыми текстовыми шорткодами, без обёртки.
 *
 * Каркас вокруг рисует тема в месте вызова {@see \Besnovatyj\Blocks\widgets\BlockArea}.
 */

/** @var array{html:string} $data */
/** @var BlockViewContext $block */
?>
<?= $block->html($data['html']) ?>
