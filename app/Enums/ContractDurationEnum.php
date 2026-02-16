<?php

namespace App\Enums;

enum ContractDurationEnum: string
{
    case ONE_MONTH = '1_month';
    case TWO_MONTHS = '2_months';
    case THREE_MONTHS = '3_months';
    case SIX_MONTHS = '6_months';
    case TWELVE_MONTHS = '12_months';
}
