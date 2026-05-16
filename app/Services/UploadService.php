<?php

declare(strict_types=1);

namespace App\Services;

use Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class UploadService {
    public static function upload($requestFile, $folder) {
        if (Auth::user() && Auth::user()->school_id) {
            $folder = Auth::user()->school_id.'/'.$folder;
        } else {
            $folder = 'super-admin/'.$folder;
        }
        $file_name = uniqid('', true) . time() . '.' . $requestFile->getClientOriginalExtension();
        if (in_array($requestFile->getClientOriginalExtension(), ['jpg', 'jpeg', 'png'])) {
            // Check the Extension should be jpg or png and do compression
            $manager = new ImageManager(new Driver());
            $image = $manager->read($requestFile);
            $encodedImage = $image->toJpeg(60);
            Storage::disk('public')->put($folder . '/' . $file_name, $encodedImage);
        } else {
            // Else assign file as it is
            $file = $requestFile;
            $file->storeAs($folder, $file_name, 'public');
        }
        return $folder . '/' . $file_name;
    }

    /**
     * @param $image = rawOriginalPath
     * @return bool
     */
    public static function delete($image) {
        if ($image && Storage::disk('public')->exists($image)) {
            return Storage::disk('public')->delete($image);
        }


        //Image does not exist in server so feel free to upload new image
        return true;
    }

}
