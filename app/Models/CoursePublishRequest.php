<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursePublishRequest extends Model
{
    protected $fillable = [
        'course_id',
        'user_id',
        'category',
        'uploaded_content',
        'status',
        'admin_notes',
        'attachment_path'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}
