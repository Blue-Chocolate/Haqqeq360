<?php

// app/Models/CaseStudyAnswerFile.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseStudyAnswerFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'answer_id',
        'file_path',
        'original_name',
    ];

    public function answer()
    {
        return $this->belongsTo(CaseStudyAnswer::class, 'answer_id');
    }

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }
}