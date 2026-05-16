<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageService {
    public static function compression($requestImage, $folder) {
        $file_name = uniqid('', true) . time() . '.' . $requestImage->getClientOriginalExtension();
        $manager = new ImageManager(new Driver());
        $image = $manager->read($requestImage);
        $encodedImage = $image->toJpeg(60);
        Storage::disk('public')->put($folder . '/' . $file_name, $encodedImage);
        return $folder . '/' . $file_name;
    }

    public static function delete($image) {
        if (Storage::disk('public')->exists($image)) {
            return Storage::disk('public')->delete($image);
        }
        //Image does not exist in server so feel free to upload new image
        return true;
    }

}
