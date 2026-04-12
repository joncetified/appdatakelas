<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait HasAuditTrail
{
    public static function bootHasAuditTrail(): void
    {
        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive(static::class), true);

        static::creating(function (Model $model): void {
            $userId = Auth::id();

            if (! $userId) {
                return;
            }

            if (self::hasColumn($model, 'created_by') && blank($model->getAttribute('created_by'))) {
                $model->setAttribute('created_by', $userId);
            }

            if (self::hasColumn($model, 'updated_by') && blank($model->getAttribute('updated_by'))) {
                $model->setAttribute('updated_by', $userId);
            }
        });

        static::updating(function (Model $model): void {
            if (Auth::id() && self::hasColumn($model, 'updated_by')) {
                $model->setAttribute('updated_by', Auth::id());
            }
        });

        if ($usesSoftDeletes) {
            static::deleting(function (Model $model): void {
                if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                    return;
                }

                if (Auth::id() && self::hasColumn($model, 'deleted_by')) {
                    $model->forceFill(['deleted_by' => Auth::id()])->saveQuietly();
                }
            });

            static::restoring(function (Model $model): void {
                if (self::hasColumn($model, 'deleted_by')) {
                    $model->setAttribute('deleted_by', null);
                }

                if (Auth::id() && self::hasColumn($model, 'updated_by')) {
                    $model->setAttribute('updated_by', Auth::id());
                }
            });
        }
    }

    private static function hasColumn(Model $model, string $column): bool
    {
        return Schema::hasColumn($model->getTable(), $column);
    }
}
