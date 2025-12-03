<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnowledgeBaseArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->when($request->routeIs('api.kb.show'), $this->content),
            'featured_image' => $this->featured_image 
                ? asset('storage/' . $this->featured_image) 
                : null,
            'views_count' => $this->views_count,
            'published_at' => $this->published_at?->format('Y-m-d H:i:s'),
            'published_at_human' => $this->published_at?->diffForHumans(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            
            // Relationships
            'course' => $this->whenLoaded('course', function () {
                return [
                    'id' => $this->course->id,
                    'title' => $this->course->title,
                    'slug' => $this->course->slug,
                ];
            }),
            
            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'name' => $this->author->name,
                ];
            }),
            
            'tags' => $this->whenLoaded('tags', function () {
                return $this->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                        'color' => $tag->color,
                    ];
                });
            }),
            
            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'file_name' => $attachment->file_name,
                        'file_url' => $attachment->file_url,
                        'file_type' => $attachment->file_type,
                        'file_size' => $attachment->file_size,
                        'file_size_formatted' => $attachment->file_size_formatted,
                    ];
                });
            }),
        ];
    }
}

// ====================================

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class KnowledgeBaseArticleCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
            ],
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
        ];
    }
}

// ====================================

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleTagResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'color' => $this->color,
            'articles_count' => $this->when(
                $this->relationLoaded('articles') || isset($this->articles_count),
                $this->articles_count ?? $this->articles->count()
            ),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}