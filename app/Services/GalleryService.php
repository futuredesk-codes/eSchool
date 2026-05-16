<?php

namespace App\Services;

use App\Models\Media;
use App\Models\MediaFile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GalleryService
{
    public function getAlbums(int $limit = 5)
    {
        return Media::query()
            ->select(['id', 'name', 'thumbnail'])
            ->where('type', 1)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn($album) => $this->formatAlbum($album));
    }

    public function paginateAlbums(int $perPage, int $page): LengthAwarePaginator
    {
        return Media::query()
            ->select(['id', 'name', 'thumbnail'])
            ->where('type', 1)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($album) => $this->formatAlbum($album));
    }

    public function getAlbumImages(int $albumId)
    {
        return MediaFile::where('media_id', $albumId)
            ->orderBy('id')
            ->pluck('file_url')
            ->map(fn($url) => $url ? asset($url) : null)
            ->filter()
            ->values();
    }

    public function getVideos(int $limit = 5)
    {
        return Media::query()
            ->select(['youtube_url'])
            ->where('type', 2)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn($video) => $this->formatVideo($video->youtube_url));
    }

    public function paginateVideos(int $perPage, int $page): LengthAwarePaginator
    {
        return Media::query()
            ->select(['youtube_url'])
            ->where('type', 2)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($video) => $this->formatVideo($video->youtube_url));
    }

    public function getGallery(?int $limit = 5): ?array
    {
        $albums = $this->getAlbums($limit);
        $videos = $this->getVideos($limit);

        if ($albums->isEmpty() && $videos->isEmpty()) {
            return null;
        }

        return [
            'albums' => $albums->isEmpty() ? null : $albums,
            'videos' => $videos->isEmpty() ? null : $videos,
        ];
    }

    private function formatAlbum($album): array
    {
        return [
            'id' => $album->id,
            'title' => $album->name,
            'thumbnail_url' => $album->thumbnail ? asset($album->thumbnail) : null,
        ];
    }

    private function formatVideo(string $url): string
    {
        return filter_var($url, FILTER_VALIDATE_URL)
            ? $url
            : asset($url);
    }
}
