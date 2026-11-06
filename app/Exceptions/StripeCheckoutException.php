<?php

namespace App\Exceptions;

use RuntimeException;

class StripeCheckoutException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Stripe could not start the checkout session.');
    }

    public function userMessage(): string
    {
        return 'We could not start checkout right now. No credits were charged. Please try again.';
    }
}
