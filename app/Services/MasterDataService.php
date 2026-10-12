<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MasterDataService
{
    public function __construct(private readonly AdminAuditLogger $auditLogger) {}

    /** @param class-string<Model> $modelClass */
    public function create(string $modelClass, array $attributes): Model
    {
        return DB::transaction(function () use ($modelClass, $attributes) {
            $model = $modelClass::create($attributes + ['is_active' => true]);
            $this->auditLogger->record('master_data.created', $model, ['after' => $this->snapshot($model)]);

            return $model;
        });
    }

    public function update(Model $model, array $attributes): Model
    {
        return DB::transaction(function () use ($model, $attributes) {
            $model = $model->newQuery()->lockForUpdate()->findOrFail($model->getKey());
            $before = $this->snapshot($model);
            $model->fill($attributes)->save();

            $this->auditLogger->record('master_data.updated', $model, [
                'before' => $before,
                'after' => $this->snapshot($model),
            ]);

            return $model;
        });
    }

    public function delete(Model $model): void
    {
        DB::transaction(function () use ($model) {
            $model = $model->newQuery()->lockForUpdate()->findOrFail($model->getKey());
            $before = $this->snapshot($model);
            $model->delete();

            $this->auditLogger->record('master_data.deleted', $model, ['before' => $before]);
        });
    }

    private function snapshot(Model $model): array
    {
        return collect($model->getAttributes())
            ->except(['created_at', 'updated_at', 'deleted_at'])
            ->all();
    }
}
