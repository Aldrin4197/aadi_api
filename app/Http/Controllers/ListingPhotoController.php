<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
class ListingPhotoController extends Controller {
    public function show(string $owner, string $filename) {
        abort_unless(preg_match('/^[A-Za-z0-9]{26}$/D',$owner) && preg_match('/^[A-Za-z0-9_-]+\.(jpg|jpeg|png|webp)$/iD',$filename),404);
        $disk=Storage::disk('public');$path='listing-photos/'.$owner.'/'.$filename;
        abort_unless($disk->exists($path),404);
        $mime=$disk->mimeType($path);
        abort_unless(in_array($mime,['image/jpeg','image/png','image/webp'],true),404);
        return $disk->response($path,null,['Content-Type'=>$mime,'Cache-Control'=>'public, max-age=86400','X-Content-Type-Options'=>'nosniff']);
    }
}
