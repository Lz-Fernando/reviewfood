<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case DRAFT = 'DRAFT';
    case COMPLETED = 'COMPLETED';
}
