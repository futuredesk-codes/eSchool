<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\MediaFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Media extends Model
{
    use HasFactory;

    protected $table = 'medias';

    protected $appends = [
        'embeded_url'
    ];

    public function getThumbnailAttribute($value) {
            return url(Storage::url($value));
    }

    public function files()
    {
        return $this->hasMany(MediaFile::class);
    }

    public function getEmbededUrlAttribute() {
        $result = ['embedUrl' => null, 'thumbnailUrl' => null];
        if (!empty($this->youtube_url)) {
            // Remove null bytes and other problematic characters
            $youtube_url = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', (string) $this->youtube_url);
            
            // return pathinfo(url(Storage::url($this->file_url)), PATHINFO_EXTENSION);
            $pattern = '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
            // Check if the URL matches the pattern
            if (preg_match($pattern, $youtube_url, $matches)) {
                // Extract Video ID
                $videoId = $matches[1];
                // Construct Embed URL
                $result['embedUrl'] = "https://www.youtube.com/embed/$videoId";
                $result['thumbnailUrl'] = "http://img.youtube.com/vi/$videoId/hqdefault.jpg";
                return $result;
            }
            // Return null if URL doesn't match the pattern
            return null;
        }
        return "";
    }

    // public function getOriginalUrlAttribute() {
    //     return $this->getRawOriginal('youtube_url');
    // }
}
