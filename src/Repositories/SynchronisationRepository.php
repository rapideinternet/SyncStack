<?php

namespace RapideSoftware\SyncStack\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use RapideSoftware\SyncStack\Models\Synchronisation;

class SynchronisationRepository
{
    protected function builder(): Builder {
        return Synchronisation::query();
    }

    public function hasRun($syncPath): bool {
        return $this->builder()->where('path', $syncPath)->exists();
    }

    public function lastBatchNumber(): int {
        return $this->builder()->max('batch') ?? 0;
    }

    /**
     * @return Collection<Synchronisation>
     */
    public function lastBatch(): Collection {
        return $this->builder()->where('batch', $this->builder()->max('batch'))->get();
    }

    public function byBatch(int $batch): Collection {
        return $this->builder()->where('batch', $batch)->get();
    }

    public function byPathLike(string $path): Collection {
        $builder = $this->builder();
        $escapedPath = strtr($path, [
            '!' => '!!',
            '%' => '!%',
            '_' => '!_',
        ]);
        $operator = $builder->getModel()->getConnection()->getDriverName() === 'pgsql'
            ? 'ILIKE'
            : 'LIKE';
        $column = $builder->getQuery()->getGrammar()->wrap('path');

        return $builder
            ->whereRaw($column.' '.$operator." ? ESCAPE '!'", ['%'.$escapedPath.'%'])
            ->get();
    }
}
