<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Unused    = 'unused';
    case Used      = 'used';
    case Expired   = 'expired';
    case Cancelled = 'cancelled';
}
