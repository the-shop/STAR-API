<?php
/**
 * Created by PhpStorm.
 * User: debeli
 * Date: 17.3.2016.
 * Time: 11:00
 */
namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['task'];
}