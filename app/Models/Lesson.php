<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'unit_id',
        'title',
        'content',
        'order',
        'video_url',
        'resource_link',
        'attachment_path',
        'published',
    ];

    protected $casts = [
        'published' => 'boolean',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    // FIX — safest correct way to get course of a lesson in polymorphic setup
    public function course()
    {
        return $this->unit->unitable instanceof Course
            ? $this->unit->unitable
            : null;
    }
}
