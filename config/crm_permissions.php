<?php

return [
    'default_role_actions' => [
        'super-admin' => '*',
        'admin'       => ['view', 'create', 'edit', 'delete', 'export'],
        'manager'     => ['view', 'create', 'edit'],
        'salesperson' => ['view', 'create'],
    ],

    'model_overrides' => [
        'Lead' => ['admin' => ['view', 'create', 'edit']],
    ],
];
