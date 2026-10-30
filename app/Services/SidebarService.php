<?php

namespace App\Services;

use App\Models\User;

class SidebarService
{
    public function getMenus(User $user): array
    {
        $menus = [
            [
                'label' => 'Dashboard',
                'icon' => 'layout-dashboard',
                'url' => route('dashboard'),
                'active' => request()->routeIs('dashboard'),
                'children' => [],
            ],
        ];

        if ($user->hasRole('super-admin', 'web')) {
            $menus[] = [
                'label' => 'Access Control',
                'icon' => 'shield',
                'url' => null,
                'active' => request()->routeIs('admin.users.*')
                    || request()->routeIs('admin.roles.*')
                    || request()->routeIs('admin.permissions.*'),
                'children' => [
                    [
                        'label' => 'Users',
                        'icon' => 'users',
                        'url' => route('admin.users.index'),
                        'active' => request()->routeIs('admin.users.*'),
                    ],
                    [
                        'label' => 'Roles',
                        'icon' => 'user-check',
                        'url' => route('admin.roles.index'),
                        'active' => request()->routeIs('admin.roles.*'),
                    ],
                    [
                        'label' => 'Permissions',
                        'icon' => 'key',
                        'url' => route('admin.permissions.index'),
                        'active' => request()->routeIs('admin.permissions.*'),
                    ],
                ],
            ];
        }


if ($user->hasCrmPermission('view.lead')) {

    $leadRoute = 'lead-management.index';

    $menus[] = [
        'label' => 'Lead Management',
        'icon' => 'funnel',
        'url' => null,
        'active' => request()->routeIs('lead-management.*'),

       
'children' => [
    [
        'label' => 'Total Leads',
        'icon' => 'users',
        'url' => route($leadRoute),
        'view' => 'total',
        'active' => false,
    ],
    [
        'label' => 'Performance & Ranking',
        'icon' => 'chart-no-axes-column',
        'url' => route($leadRoute),
        'view' => 'performance',
        'active' => false,
    ],
    [
        'label' => 'Weekly Targets',
        'icon' => 'calendar-check',
        'url' => route($leadRoute),
        'view' => 'weekly-targets',
        'active' => false,
    ],
],

    ];
}


        if ($user->hasCrmPermission('edit.lead')) {
            $menus[] = [
                'label' => 'Project Management',
                'icon' => 'home',
                'url' => route('admin.projects.index'),
                'active' => request()->routeIs('admin.projects.*'),
                'children' => [],
            ];
        }

        // Existing web routes only require authentication.
        $menus[] = [
            'label' => 'Target Management',
            'icon' => 'target',
            'url' => route('admin.targets.index'),
            'active' => request()->routeIs('admin.targets.*'),
            'children' => [],
        ];

        if ($user->hasRole('super-admin', 'web')) {
            $menus[] = [
                'label' => 'Settings',
                'icon' => 'dollar-sign',
                'url' => route('admin.settings.index'),
                'active' => request()->routeIs('admin.settings.*'),
                'children' => [],
            ];
        }

        return $menus;
    }
}
