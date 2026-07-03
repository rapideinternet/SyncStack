<?php

namespace RapideSoftware\SyncStack\Commands;

use Exception;
use RapideSoftware\SyncStack\Models\Synchronisation;
use RapideSoftware\SyncStack\Repositories\SynchronisationRepository;
use RapideSoftware\SyncStack\Commands\Base\InstantiatorCommand;
use RapideSoftware\SyncStack\Commands\Traits\LocationTrait;

class RollbackSynchronisationCommand extends InstantiatorCommand {

    use LocationTrait;

    protected $signature = 'sync:rollback
    {--continueOnFailure : By default it stops rolling back syncs if one errors out. If syncs are order agnostic, you may want to continue to rollback the next sync if one fails.}
    {--batch=: Rollback a specific batch}
    {--path=: rollback a specific sync by path. This uses a case insensitive like, so partial matches can be used to grab more than one sync}';

    protected $description = 'Rollback last or specific batch of sync commands.';

    public function handle(SynchronisationRepository $syncRepository): void {
        $batch = $this->option('batch');
        $path = $this->option('path');
        $hasBatch = $batch !== null && $batch !== '';
        $hasPath = $path !== null && $path !== '';

        // Short circuit and inform batch and name are mutually exclusive
        if ($hasBatch && $hasPath) {
            $this->warn('Batch and path are mutually exclusive.');
            return;
        }

        if ($hasBatch) {
            if (!ctype_digit((string) $batch) || (int) $batch < 1) {
                $this->warn('Batch must be a number and above 0.');
                return;
            }
            $syncs = $syncRepository->byBatch((int) $batch);
            $this->info('Rolling back on batch '.$batch.'.');
        } elseif ($hasPath) {
            $syncs = $syncRepository->byPathLike($path);
            $this->info('Rolling back on (partial) path '.$path.'.');
        } else {
            $syncs = $syncRepository->lastBatch();
            $this->info('Rolling back last batch');
        }

        // Short circuit and inform if no syncs are found
        if(count($syncs) === 0) {
            $this->info('No synchronisations found. Nothing to rollback.');
        }

        /** @var Synchronisation $sync */
        foreach($syncs as $sync) {
            try {
                if (($instance = $this->getInstance(base_path().$sync->path)) === null) {
                    continue;
                }

                app()->call([$instance, 'rollback']);

                $sync->deleteOrFail();
            } catch (Exception $exception) {
                $this->error('Something went wrong! Check your rollback code. It is possible a partial rollback has happened.');
                $this->error($exception->getMessage());
                $this->error($exception->getTraceAsString());

                if (!$this->option('continueOnFailure')) {
                    return;
                }
            }
        }
    }

    protected function hasRequiredMethod(object $instance): bool {
        return !method_exists($instance, 'rollback');
    }
}
