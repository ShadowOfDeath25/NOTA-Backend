<?php

namespace App\Exceptions;

use Exception;

class InviteAlreadyUsedException extends Exception
{
    public function __construct(string $message = "This invite link has already been used.", int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
