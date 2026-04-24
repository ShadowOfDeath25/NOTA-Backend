<?php

namespace App\Enums;

enum Role: string
{
    case OWNER = 'owner';
    case VIEWER = 'viewer';
    case EDITOR = 'editor';
    case ADMIN = 'admin';

}
