<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\KnowledgeBaseArticle;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ArticleTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeBaseArticle::class, 'article_tag_pivot', 'tag_id', 'article_id')
            ->withTimestamps();
    }
}