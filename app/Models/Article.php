<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function summary(): string
    {
        return filled($this->excerpt) ? $this->excerpt : Str::limit($this->plainBody(), 240);
    }

    private function plainBody(): string
    {
        return trim(strip_tags(Str::markdown((string) $this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ])));
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
