<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
class ListingPhotoController extends Controller {
    public function show(string $owner, string $filename) {
        abort_unless(preg_match('/^[A-Za-z0-9]{26}$/D',$owner) && preg_match('/^[A-Za-z0-9_-]+\.(jpg|jpeg|png|webp)$/iD',$filename),404);
        $disk=Storage::disk('public');$path='listing-photos/'.$owner.'/'.$filename;
        abort_unless($disk->exists($path),404);
        $stream=$disk->readStream($path);
        $head=is_resource($stream)?fread($stream,512):'';
        if(is_resource($stream))fclose($stream);
        // Detect from content, not the extension, so a renamed non-image is never served as an image.
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->buffer((string)$head);
        abort_unless(in_array($mime,['image/jpeg','image/png','image/webp'],true),404);
        return $disk->response($path,null,['Content-Type'=>$mime,'Cache-Control'=>'public, max-age=86400','X-Content-Type-Options'=>'nosniff']);
    }
}
