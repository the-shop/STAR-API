<?php

namespace App\Exceptions;

/**
 * Class ValidationException
 * @package App\Exceptions
 */
class DynamicValidationException extends \Exception
{
    /**
     * Array of validation messages
     * @var array
     */
    private $messages = [];

    /**
     * ValidationException constructor.
     * @param string $messages
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($messages, $code, \Exception $previous = null)
    {
        parent::__construct(json_encode($messages), $code, $previous);
        $this->messages = $messages;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
