<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class GalleryItem extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const TYPE_PHOTO = 'photo';

    public const TYPE_VIDEO = 'video';

    protected $fillable = [
        'type',
        'title',
        'description',
        'youtube_url',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Media collection untuk foto galeri (satu gambar per item, khusus type=photo).
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->useDisk('public');
    }

    public function isPhoto(): bool
    {
        return $this->type === self::TYPE_PHOTO;
    }

    public function isVideo(): bool
    {
        return $this->type === self::TYPE_VIDEO;
    }

    /**
     * URL gambar (type=photo), atau null bila belum ada.
     */
    public function imageUrl(): ?string
    {
        return $this->getFirstMediaUrl('image') ?: null;
    }

    /**
     * ID video YouTube dari youtube_url, atau null bila tidak valid/kosong.
     */
    public function youtubeId(): ?string
    {
        return self::extractYoutubeId($this->youtube_url);
    }

    /**
     * Thumbnail bawaan YouTube untuk video ini (dipakai sebagai gambar preview
     * di galeri sebelum video diputar), atau null bila bukan video valid.
     */
    public function youtubeThumbnailUrl(): ?string
    {
        $id = $this->youtubeId();

        return $id ? "https://img.youtube.com/vi/{$id}/hqdefault.jpg" : null;
    }

    /**
     * URL embed (privacy-enhanced, youtube-nocookie.com) untuk diputar langsung
     * di halaman ini (bukan redirect ke youtube.com), atau null bila tidak valid.
     */
    public function youtubeEmbedUrl(): ?string
    {
        $id = $this->youtubeId();

        return $id ? "https://www.youtube-nocookie.com/embed/{$id}?autoplay=1" : null;
    }

    /**
     * Ambil ID video dari berbagai format link YouTube:
     * watch?v=, youtu.be/, /embed/, /shorts/, dengan atau tanpa query lain.
     */
    public static function extractYoutubeId(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (preg_match('#(?:youtube(?:-nocookie)?\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})#i', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
