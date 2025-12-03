<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class KnowledgeBaseArticle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'course_id',
        'created_by',
        'status',
        'views_count',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'views_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
            
            if (empty($article->excerpt)) {
                $article->excerpt = Str::limit(strip_tags($article->content), 200);
            }
        });

        static::updating(function ($article) {
            if ($article->isDirty('title') && empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ArticleTag::class, 'article_tag_pivot', 'article_id', 'tag_id')
            ->withTimestamps();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ArticleAttachment::class, 'article_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%")
              ->orWhere('excerpt', 'like', "%{$search}%");
        });
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' 
            && $this->published_at !== null 
            && $this->published_at->isPast();
    }
}