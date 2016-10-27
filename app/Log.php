<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class Log extends Model
{
    protected $fillable = ['name', 'id', 'date', 'ip'];
}
