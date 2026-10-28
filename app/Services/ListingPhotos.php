<?php
namespace App\Services;
use Illuminate\Support\Facades\Storage;
class ListingPhotos {
    public static function url(string $path): string {
        return route('listing-photos.show', ['owner'=>basename(dirname($path)), 'filename'=>basename($path)]);
    }
    public static function resolve($value): array {
        $photos=is_string($value) ? json_decode($value,true) : $value;
        if (!is_array($photos)) return [];
        return array_map(function ($photo) {
            if (!is_string($photo)) return $photo;
            $path=parse_url($photo,PHP_URL_PATH);
            if (!is_string($path)) return $photo;
            if (preg_match('~/media/listing-photos/([A-Za-z0-9]{26})/([A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp))$~i',$path,$match)) {
                return self::url('listing-photos/'.$match[1].'/'.$match[2]);
            }
            // Only repair legacy paths that identify a file on this application's public disk.
            if (preg_match('~(?:^|/)storage/listing-photos/([A-Za-z0-9]{26})/([A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp))$~i',$path,$match)) {
                $local='listing-photos/'.$match[1].'/'.$match[2];
                if (Storage::disk('public')->exists($local)) return self::url($local);
            }
            return $photo;
        },$photos);
    }
}
