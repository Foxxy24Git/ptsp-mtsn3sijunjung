<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Post yang berada di kategori ini.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
