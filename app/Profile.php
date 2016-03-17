<?php
/**
 * Created by PhpStorm.
 * User: debeli
 * Date: 16.3.2016.
 * Time: 11:18
 */
namespace App;



use Jenssegers\Mongodb\Eloquent\Model;

class Profile extends Model
{
    protected $fillable =['user'];
}