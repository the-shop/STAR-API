<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

/**
 * Class GenericValidation
 * @package App
 */
class Validation extends Eloquent
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'resource',
        'fields',
        'messages',
        'acl'
    ];

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields ? $this->fields : [];
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages ? $this->messages : [];
    }
}
