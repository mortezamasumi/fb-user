<?php

return [
    'remove_unattend_user_hours' => env('REMOVE_UN_ATTEND_USER_HOURS', 48),
    'navigation' => [
        'model_label' => 'fb-user::fb-user.navigation.label',
        'plural_model_label' => 'fb-user::fb-user.navigation.plural_label',
        'group' => 'fb-user::fb-user.navigation.group',
        'parent_item' => null,
        'icon' => 'heroicon-o-user',
        'active_icon' => 'heroicon-s-user',
        'badge' => true,
        'badge_tooltip' => null,
        'sort' => 10,
    ],
];
