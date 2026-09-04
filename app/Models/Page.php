<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Page extends Model implements HasMedia
{
    use InteractsWithMedia;

    /** Tipe berkas yang diterima koleksi media 'documents' (dipakai juga oleh PageForm). */
    public const DOCUMENT_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
    ];

    /**
     * Media collection untuk gambar unggulan halaman & dokumen terkait yang
     * bisa diunduh pengunjung. Dipisah karena 'featured' dirender sebagai
     * <img> banner (lihat pages/show.blade.php) sehingga tidak bisa dobel
     * fungsi menampung berkas PDF/DOC.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured')
            ->singleFile();

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(self::DOCUMENT_MIME_TYPES);
    }

    /**
     * Pratinjau (thumbnail) halaman pertama untuk dokumen PDF, dipakai kartu
     * "Dokumen Terkait" di halaman publik. Cuma berlaku buat PDF -- Word
     * (application/msword & .docx) tidak bisa dirender dengan cara ini, jadi
     * otomatis tetap pakai ikon generik (lihat page-documents.blade.php).
     * Butuh Ghostscript & paket spatie/pdf-to-image di server.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('preview')
            ->width(400)
            ->performOnCollections('documents')
            ->nonQueued();
    }
}
