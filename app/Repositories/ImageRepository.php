<?php
namespace App\Repositories;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as InterventionImage;
use App\Models\Image;

class ImageRepository
{
public function store($request)
{
    // Save image
    $path = Storage::disk('images')->put('', $request->file('image'));
    // Save thumb
    $image = InterventionImage::make($request->file('image'))->widen(500);
    Storage::disk('thumb')->put($path, $image->encode());
    // Save in base
    $image = new Image;
    $image->description = $request->description;
    $image->categorie_id = $request->category_id;
    $image->name = $path;
    $image->user_id = auth()->id();
    $image->save();
}

public function getImagesForCategory($slug)
{
    return Image::latestWithUser()->whereHas('categorie', function ($query) use ($slug) {
        $query->whereSlug($slug);
    })->paginate(config('app.pagination'));
}

public function getOrphans()
{
    $files = collect(Storage::disk('images')->files());
    $images = Image::select('name')->get()->pluck('name');
    return $files->diff($images);
}

public function getImagesForUser($id)
{
    return Image::latestWithUser()->whereHas('user', function ($query) use ($id) {
        $query->whereId($id);
    })->paginate(config('app.pagination'));
}

public function destroyOrphans()
{
    $orphans = $this->getOrphans ();
    foreach($orphans as $orphan) {
        Storage::disk('images')->delete($orphan);
        Storage::disk('thumb')->delete($orphan);
    }
}

}
