# Sync Stack

A lightweight layer for running post-deploy synchronisations, similar to migrations but kept separate. It only executes sync files that have not been run before, making it handy for CI/CD pipelines or multi-database setups where environments may be out of sync.

## Requirements
- PHP ^8.0
- Laravel 11.41.3^/12^

## Installation
```bash
composer require rapide-software/sync-stack
```
The service provider is auto-discovered.

## Setup
1) Create the tracking table migration (defaults to `synchronisations`):
```bash
php artisan sync:migrate
php artisan migrate
```
Use `--path` to place the migration elsewhere or `--realpath` for absolute paths.

2) (Optional) Choose where your sync files live. By default they sit in `app/Synchronisations` using StudlyCase folders. Pass `--location` to any sync command to override, or `--fromBasePath` to target an absolute path under `base_path()`.

## Creating synchronisations
Generate a new sync file (timestamped automatically):
```bash
php artisan sync:create --name="update_permissions" --location=Synchronisations
```
`--abstractClass` lets you extend your own base class for shared helpers. As of yet, this feature is very barebones and simply writes the extends line for you.

Each sync is an anonymous class with two hooks:
```php
return new class {
    public function sync(): void {
        // Synchronize what you want synchronized.
    }

    public function rollback(): void {
        // Rollback logic to undo your synchronization
    }
};
```

## Running synchronisations
Execute only the sync files that have not run yet:
```bash
php artisan sync:run --location=Synchronisations
```
- Files are discovered recursively under `--location` and keyed by their path in the `synchronisations` table.
- Each run uses the next batch number; `--continueOnFailure` keeps going after an error.

## Rolling back
Undo the most recent batch of syncs:
```bash
php artisan sync:rollback --location=Synchronisations
```
- By default only the latest batch is rolled back; earlier batches stay intact.
- Use `--batch=x` to rollback a specific batch number.
- Use `--path=x` to rollback using a like match on the path name, so partial paths are also allowed. Use with care!
- Use `--continueOnFailure` to attempt the rest even if one rollback fails.

## License
MIT
