<?php

namespace App\Exceptions;

/**
 * Class UserInputException
 * @package App\Exceptions
 */
class UserInputException extends \Exception
{
    /**
     * UserInputException constructor.
     * @param string $message
     * @param \Exception|null $previous
     */
    public function __construct($message, \Exception $previous = null)
    {
        parent::__construct($message, 400, $previous);
    }
}
