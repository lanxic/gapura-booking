<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending          = 'pending';
    case AwaitingPayment  = 'awaiting_payment';
    case DpPaid           = 'dp_paid';
    case Paid             = 'paid';
    case Confirmed        = 'confirmed';
    case Cancelled        = 'cancelled';
    case Refunded         = 'refunded';
    case Expired          = 'expired';
}
