<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

return [[
    'label' => 'Блоки темы',
    'iconClass' => 'bi bi-grid-1x2 me-1',
    'url' => ['/Blocks/backend/default/index'],
    'active' => static function () {
        return str_contains(\Yii::$app->request->url, 'Blocks/backend');
    },
    '_meta' => [
        'placements' => [
            [
                'location' => 'right-sidebar',
                'group' => 'Content',
                'groupIcon' => 'bi bi-pencil-square',
                'priority' => 100,
                'groupPriority' => 90,
            ],
        ],
    ],
]];
