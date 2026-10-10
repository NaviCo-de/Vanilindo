<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['title', 'slug', 'excerpt', 'body', 'external_url', 'cover_image_path', 'cover_image_alt', 'is_published', 'published_at', 'seo_title', 'seo_description'])]
class Article extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Article $article): void {
            if (blank($article->slug)) {
                $title = Str::limit(Str::slug($article->title), 140, '') ?: 'blog';
                $article->slug = $title . '-' . Str::lower((string) Str::ulid());
            }
        });
    }

    public function externalLink(): ?string
    {
        $url = trim((string) $this->external_url);

        return filter_var($url, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
                ? $url
                : null;
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
