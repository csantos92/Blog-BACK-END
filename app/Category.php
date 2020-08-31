<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    //DB Table
    protected $table = 'categories';

    //One to many
    public function posts(){
        return $this->hasMany('App\Post');
    }
}
