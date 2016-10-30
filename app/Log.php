<?php

namespace App;

class Log extends StarModel
{
    protected $fillable = ['name', 'id', 'date', 'ip', 'uri', 'method'];
}
