<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workshop extends Model
{
   protected $fillable = [
       'title',
       'slug',
       'description',
       'image_path',
       'duration_hours',
       'price',
       'discounted_price',
       'level',
       'mode',
       'cover_image',
       'status',
       'instructor_id',
   ];
}
