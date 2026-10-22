<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name'])]
class Tag extends Model
{
    use HasFactory;

    /**
     * Get the articles associated with the tag.
     */
    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_tag');
    }
}
