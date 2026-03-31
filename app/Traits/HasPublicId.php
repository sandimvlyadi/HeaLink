<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasPublicId
{
    /**
     * Boot the trait — auto-generate UUID on model creation.
     */
    public static function bootHasPublicId(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Route model binding pakai uuid bukan id.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Semua response API pakai uuid.
     */
    public function getPublicId(): string
    {
        return $this->uuid;
    }
}
