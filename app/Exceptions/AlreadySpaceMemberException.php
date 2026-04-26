<?php

namespace App\Exceptions;

use Exception;

class AlreadySpaceMemberException extends Exception
{
    public function __construct(string $message = "You are already a member of this space.", int $code = 409)
    {
        parent::__construct($message, $code);
    }
}
