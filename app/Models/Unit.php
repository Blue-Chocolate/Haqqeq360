<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'order',
        'unitable_id',
        'unitable_type',
    ];

    public function unitable()
    {
        return $this->morphTo();
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }
}
