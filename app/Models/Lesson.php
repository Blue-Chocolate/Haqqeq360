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

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
