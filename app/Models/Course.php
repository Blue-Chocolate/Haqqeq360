<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Course extends Model
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'duration_weeks',
        'level',
        'seats',
        'mode',
        'cover_image',
        'status',
        'instructor_id',
        'price',
        'discounted_price',
        'image_path',
    ];

    protected $casts = [
        'duration_weeks' => 'integer',
        'seats' => 'integer',
        'price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function user(): BelongsTo
    {
        return $this->instructor();
    }

    public function enrollments(): MorphMany
    {
        return $this->morphMany(Enrollment::class, 'enrollable');
    }

    public function publishRequests()
    {
        return $this->hasMany(CoursePublishRequest::class);
    }

    public function plans(): MorphMany
    {
        return $this->morphMany(Plan::class, 'planable');
    }

    // public function units(): MorphMany
    // {
    //     return $this->morphMany(Unit::class, 'unitable')->orderBy('order');
    // }

    // FIXED — avoid polymorphic hasManyThrough
    public function lessons()
    {
        return Lesson::query()
            ->select('lessons.*')
            ->join('units', 'lessons.unit_id', '=', 'units.id')
            ->where('units.unitable_id', $this->id)
            ->where('units.unitable_type', Course::class);
    }

    // FIXED — since assignment has course_id
    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function getAvailableSeatsAttribute(): int
    {
        $enrollmentsCount = $this->enrollments_count ?? $this->enrollments()->count();
        return max(0, $this->seats - $enrollmentsCount);
    }
        public function units()
    {
        return $this->hasMany(Unit::class)->orderBy('order');
    }

        public function knowledgeBaseArticles()
    {
        return $this->hasMany(KnowledgeBaseArticle::class)->orderBy('created_at', 'desc');
    }
    public function tests()
    {
        return $this->hasMany(Test::class);
    }
    public function discussions()
    {
        return $this->hasMany(Discussion::class);
    }
}


