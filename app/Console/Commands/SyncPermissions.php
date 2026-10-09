<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;

class SyncPermissions extends Command
{
    protected $signature   = 'permission:sync {model? : Optional model name to sync (e.g. Lead)}';
    protected $description = 'Sync model permissions to the database';

    public function handle(): int
    {
        $modelArg = $this->argument('model');
        $models   = $this->resolveModels($modelArg);

        if (empty($models)) {
            $this->error('No eligible models found.');
            return self::FAILURE;
        }

        $rows = [];

        foreach ($models as $modelClass) {
            $shortName   = class_basename($modelClass);
            $permissions = $modelClass::allPermissions();

            $createdPerms = [];
            foreach ($permissions as $permName) {
                $perm = Permission::firstOrCreate(
                    ['name' => $permName, 'guard_name' => 'web']
                );
                $createdPerms[] = $perm->name;
            }

            $rows[] = [
                $shortName,
                implode(', ', $createdPerms),
                count($createdPerms),
            ];
        }

        $this->table(
            ['Model', 'Permissions', 'Count'],
            $rows
        );

        $this->info('Run php artisan permission:cache-reset in production');

        return self::SUCCESS;
    }

    private function resolveModels(?string $modelArg): array
    {
        if ($modelArg) {
            $class = "App\\Models\\{$modelArg}";
            if (!class_exists($class)) {
                $this->error("Model class {$class} not found.");
                return [];
            }
            if (!in_array(\App\Traits\HasModelPermissions::class, class_uses_recursive($class))) {
                $this->error("Model {$class} does not use HasModelPermissions trait.");
                return [];
            }
            return [$class];
        }

        // Scan all model files
        $models    = [];
        $modelPath = app_path('Models');
        $files     = File::allFiles($modelPath);

        foreach ($files as $file) {
            $class = 'App\\Models\\' . $file->getFilenameWithoutExtension();
            if (!class_exists($class)) {
                continue;
            }
            if (in_array(\App\Traits\HasModelPermissions::class, class_uses_recursive($class))) {
                $models[] = $class;
            }
        }

        return $models;
    }
}
