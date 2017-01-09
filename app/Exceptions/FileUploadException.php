<?php

namespace App\Exceptions;

/**
 * Class FileUploadException
 * @package App\Exceptions
 */
class FileUploadException extends \Exception
{
    /**
     * FileUploadException constructor.
     * @param string $message
     * @param \Exception|null $previous
     */
    public function __construct($message, \Exception $previous = null)
    {
        parent::__construct($message, 400, $previous);
    }
}
