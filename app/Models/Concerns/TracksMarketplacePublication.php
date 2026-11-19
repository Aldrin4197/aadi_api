<?php

namespace App\Models\Concerns;

use App\Models\Listing;
use Illuminate\Database\Eloquent\Model;

trait TracksMarketplacePublication
{
    protected static function bootTracksMarketplacePublication(): void
    {
        static::saving(function (Model $record): void {
            $publishedStatus = $record instanceof Listing ? 'active' : 'available';
            $status = $record->status instanceof \BackedEnum ? $record->status->value : $record->status;
            $wasPublished = $record->exists && $record->getRawOriginal('status') === $publishedStatus;

            if ($status !== $publishedStatus) {
                $record->published_at = null;
            } elseif (!$wasPublished || !$record->published_at) {
                $record->published_at = now();
            }
        });
    }
}
