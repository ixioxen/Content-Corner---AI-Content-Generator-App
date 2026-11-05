<?php

namespace App\Exceptions;

use RuntimeException;

class GeminiGenerationException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The AI provider could not generate content.');
    }

    public function userMessage(): string
    {
        return 'Content generation failed. Your credit has been refunded. Please try again.';
    }
}
