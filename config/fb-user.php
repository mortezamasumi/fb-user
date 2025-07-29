<?php

return [
    'setup' => [
        'user_id_column_type' => 'uuid',
        'remove_unattend_user_hours' => 48,
        // 'account_has_role_approval' => true,
    ],
    'navigation' => [
        'icon' => 'heroicon-o-users',
        'sort' => 9000,
        'label' => 'fb-user::fb-user.navigation.label',
        'group' => 'fb-user::fb-user.navigation.group',
        'model_label' => 'fb-user::fb-user.navigation.user',
        'plural_model_label' => 'fb-user::fb-user.navigation.users',
        'show_count' => true,
        'parent_item' => null,
        'active_icon' => null,
    ],
];
