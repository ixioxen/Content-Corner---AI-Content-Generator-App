<?php

namespace App\Enums;

enum CreditTransactionType: string
{
    case SignupBonus = 'signup_bonus';
    case Generation = 'generation';
    case Purchase = 'purchase';
    case Refund = 'refund';
    case AdminAdjustment = 'admin_adjustment';
}
