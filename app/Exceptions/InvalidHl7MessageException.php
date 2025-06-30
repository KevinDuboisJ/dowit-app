<?php

namespace App\Exceptions;

use Exception;

class InvalidHl7MessageException extends Exception
{
    private string $userMessage;

    public function __construct(string $message = 'No HL7 message content')
    {
        parent::__construct($message);
        $this->userMessage = $message;
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }
}
