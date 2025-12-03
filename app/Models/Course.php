<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use \Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;


class Course extends Model {

    use HasFactory , Notifiable, SoftDeletes;
    protected $fillable = ['title','slug','description','duration_weeks','level','seats','mode','cover_image','status','instructor_id'];
    public function instructor() { return $this->belongsTo(User::class, 'instructor_id'); }
    public function assignments() { return $this->hasMany(Assignment::class); }
    public function enrollments() { return $this->morphMany(Enrollment::class, 'enrollable'); }
    public function publishRequests()
{
    return $this->hasMany(CoursePublishRequest::class);
}
}

