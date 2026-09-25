<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'page', 'label', 'eyebrow', 'heading', 'body', 'image_path', 'image_alt', 'sort_order'])]
class ContentBlock extends Model
{
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
