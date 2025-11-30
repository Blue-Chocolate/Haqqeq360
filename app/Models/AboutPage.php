<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class AboutPage extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'hero_title',
        'hero_description',
        'hero_background_image',
        'hero_overlay_opacity',
        'about_content',
        'show_about_icons',
        'vision_title',
        'vision_content',
        'vision_icon',
        'show_vision_section',
        'is_active',
        'status',
        'display_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'show_about_icons' => 'boolean',
        'show_vision_section' => 'boolean',
        'is_active' => 'boolean',
        'hero_overlay_opacity' => 'integer',
        'display_order' => 'integer',
    ];

    /**
     * Get the hero background image URL.
     *
     * @return string|null
     */
    public function getHeroBackgroundImageUrlAttribute(): ?string
    {
        if ($this->hero_background_image) {
            return Storage::url($this->hero_background_image);
        }
        return null;
    }

    /**
     * Get the vision icon URL.
     *
     * @return string|null
     */
    public function getVisionIconUrlAttribute(): ?string
    {
        if ($this->vision_icon) {
            return Storage::url($this->vision_icon);
        }
        return null;
    }

    /**
     * Get hero description word count.
     *
     * @return int
     */
    public function getHeroDescriptionWordCountAttribute(): int
    {
        return str_word_count(strip_tags($this->hero_description ?? ''));
    }

    /**
     * Get about content word count.
     *
     * @return int
     */
    public function getAboutContentWordCountAttribute(): int
    {
        return str_word_count(strip_tags($this->about_content ?? ''));
    }

    /**
     * Get vision content word count.
     *
     * @return int
     */
    public function getVisionContentWordCountAttribute(): int
    {
        return str_word_count(strip_tags($this->vision_content ?? ''));
    }

    /**
     * Scope a query to only include active about pages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include published about pages.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }
}