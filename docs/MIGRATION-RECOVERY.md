# Resume migrations on an existing Hostinger database

`Table ... already exists` means Laravel is trying to run a create-table migration against an existing table, usually because an imported database's migration records do not match the deployed migration files.

The updated migrations check each table before creating it and check each added column before adding it. A multi-table migration still creates missing tables even if another table from that migration already exists. Existing rows are retained. These checks do not reconcile differences in existing column types, indexes, or foreign keys.

1. Export a database backup through Hostinger hPanel/phpMyAdmin.
2. Upload **all files in `database/migrations`** from this project, replacing the corresponding files on the server. Keep their original filenames. Uploading only the newest chat migrations does not fix older pending migrations.
3. In the Laravel application's directory, run:

```sh
php artisan config:clear
php artisan migrate:status
php artisan migrate --force
php artisan migrate:status
php artisan config:cache
```

The normal `migrate` command records each successfully completed or skipped-existing migration in the migrations table. Already recorded migrations remain untouched. No manual edits to migration-history rows are needed for this fix.

Do **not** run `migrate:fresh`, `migrate:refresh`, `migrate:reset`, or `migrate:rollback` to fix this error. These commands can drop existing tables or columns. In particular, rolling back a migration that adopted an existing table can still run its normal destructive `down()` method.

If a later error reports a missing column or incompatible foreign key, inspect that specific table's schema before changing it. Do not drop the table or mark the failing migration complete manually.

Local regression coverage simulates an existing populated database with missing migration history, partially existing added columns, and a partially completed multi-table migration. Hosting/MySQL changes must be verified on the server after upload.
