<?php

namespace App\Exceptions;

use Exception;

class InviteExpiredException extends Exception
{
    public function __construct(string $message = "This invite link has expired.", int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
