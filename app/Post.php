<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
        /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'content', 'category_id', 'image'
    ];

    //DB Table
    protected $table = 'posts';

    //Many to one
    public function user(){
        return $this->belongsTo('App\User', 'user_id');
    }

    //Many to one
    public function category(){
        return $this->belongsTo('App\Category', 'category_id');
    }
}
