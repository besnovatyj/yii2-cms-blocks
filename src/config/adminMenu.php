<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Contracts\adminMenu\AdminMenuLocation;
use Besnovatyj\Contracts\adminMenu\AdminMenuPlacement;

return [[
    'label' => 'Блоки темы',
    'iconClass' => 'bi bi-grid-1x2 me-1',
    'url' => ['/Blocks/backend/default/index'],
    'active' => static function () {
        return str_contains(\Yii::$app->request->url, 'Blocks/backend');
    },
    '_meta' => [
        'placements' => [
            new AdminMenuPlacement(
                location: AdminMenuLocation::RightSidebar,
                group: 'Content',
                groupIcon: 'bi bi-pencil-square',
                groupPriority: 90,
                priority: 100,
            ),
        ],
    ],
]];
