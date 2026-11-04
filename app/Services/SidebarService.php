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
                'section' => 'Overview',
                'children' => [],
            ],
        ];

        if ($user->hasRole('super-admin', 'web')) {
            $menus[] = [
                'label' => 'User Management', 'icon' => 'contact-round',
                'url' => route('admin.employees.index'),
                'active' => request()->routeIs('admin.employees.*'),
                'section' => 'Overview', 'children' => [],
            ];
            $menus[] = [
                'label' => 'Team Management', 'icon' => 'users-round',
                'url' => route('admin.teams.index'),
                'active' => request()->routeIs('admin.teams.*'),
                'section' => 'Overview', 'children' => [],
            ];
        }

        if ($user->hasCrmPermission('view.lead')) {
            $leadRoute = 'lead-management.index';

            $menus[] = [
                'label' => 'All Leads',
                'icon' => 'users-round',
                'url' => route($leadRoute),
                'view' => 'total',
                'active' => request()->routeIs('lead-management.*')
                    && (!request()->filled('attention') || request('attention') === 'unassigned'),
                'section' => 'Lead operations',
            ];

            $menus[] = [
                'label' => 'Follow-up Monitor',
                'icon' => 'list-checks',
                'url' => null,
                'active' => request()->routeIs('lead-management.*')
                    && in_array(request('attention'), ['overdue', 'no-follow-up', 'stale'], true),
                'section' => 'Lead operations',
                'children' => [
                    [
                        'label' => 'Overdue follow-ups',
                        'icon' => 'calendar-clock',
                        'url' => route($leadRoute, ['attention' => 'overdue']),
                        'attention' => 'overdue',
                    ],
                    [
                        'label' => 'No follow-up set',
                        'icon' => 'calendar-plus',
                        'url' => route($leadRoute, ['attention' => 'no-follow-up']),
                        'attention' => 'no-follow-up',
                    ],
                    [
                        'label' => 'Stale leads · 7+ days',
                        'icon' => 'hourglass',
                        'url' => route($leadRoute, ['attention' => 'stale']),
                        'attention' => 'stale',
                    ],
                ],
            ];
        }

        if ($user->hasCrmPermission('edit.lead')) {
            $menus[] = [
                'label' => 'Project Management',
                'icon' => 'building-2',
                'url' => route('admin.projects.index'),
                'active' => request()->routeIs('admin.projects.*'),
                'section' => 'Workspace',
                'children' => [],
            ];
        }

        $menus[] = [
            'label' => 'Team Chat', 'icon' => 'messages-square', 'badge' => 'messages',
            'url' => route('admin.chat.index'), 'active' => request()->routeIs('admin.chat.*'),
            'section' => 'Workspace', 'children' => [],
        ];

        // Existing web routes only require authentication.
        $menus[] = [
            'label' => 'Target Management',
            'icon' => 'target',
            'url' => route('admin.targets.index'),
            'active' => request()->routeIs('admin.targets.*'),
            'section' => 'Workspace',
            'children' => [],
        ];

        $menus[] = [
            'label' => 'Notifications',
            'icon' => 'bell',
            'url' => route('admin.notifications.index'),
            'active' => request()->routeIs('admin.notifications.*'),
            'badge' => 'notifications',
            'section' => 'Workspace',
            'children' => [],
        ];

        if ($user->hasRole('super-admin', 'web')) {
            $menus[] = [
                'label' => 'Settings',
                'icon' => 'settings-2',
                'url' => route('admin.settings.index'),
                'active' => request()->routeIs('admin.settings.*'),
                'section' => 'Administration',
                'children' => [],
            ];
        }

        if ($user->hasRole('super-admin', 'web')) {
            $menus[] = [
                'label' => 'Access Control',
                'icon' => 'shield',
                'url' => null,
                'active' => request()->routeIs('admin.roles.*')
                    || request()->routeIs('admin.permissions.*'),
                'section' => 'Administration',
                'children' => [
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

        return $menus;
    }
}
