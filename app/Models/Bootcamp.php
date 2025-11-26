<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use \Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;


class Bootcamp extends Model {
        use HasFactory , Notifiable, SoftDeletes;

    protected $fillable = ['title','description','duration_weeks','level','start_date','mode','seats','certificate','instructor_id','cover_image'];
    public function instructor() { return $this->belongsTo(User::class, 'instructor_id'); }
    public function enrollments() { return $this->morphMany(Enrollment::class, 'enrollable'); }
    public function assignments() {
    return $this->hasMany(Assignment::class);
}
}
