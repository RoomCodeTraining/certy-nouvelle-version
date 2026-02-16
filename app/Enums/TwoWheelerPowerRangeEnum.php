<?php

namespace App\Enums;

enum TwoWheelerPowerRangeEnum: string
{
    case RANGE_INFERIOR_TO_50 = 'inferior_to_50';
    case RANGE_51_99 = '51_99';
    case RANGE_100_175 = '100_175';
    case RANGE_176_350 = '176_350';
    case RANGE_350_PLUS = '350_plus';
}
