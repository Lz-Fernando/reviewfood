<?php

namespace App\Enums;

enum GroupInvitationStatus: string
{
    case PENDING = 'PENDING';
    case ACCEPTED = 'ACCEPTED';
    case DECLINED = 'DECLINED';
    case EXPIRED = 'EXPIRED';
}
