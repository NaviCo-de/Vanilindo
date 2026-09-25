<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'variety', 'summary', 'description', 'image_path', 'image_alt', 'is_published', 'is_featured', 'sort_order', 'seo_title', 'seo_description'])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
