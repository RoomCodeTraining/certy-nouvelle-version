<?php

namespace App\Enums;

enum VpPowerRangeEnum: string
{
    case RANGE_2_4 = '2_4';
    case RANGE_3_6 = '3_6';
    case RANGE_5_6 = '5_6';
    case RANGE_7_8 = '7_8';
    case RANGE_7_9 = '7_9';
    case RANGE_9_PLUS = '9_plus';
    case RANGE_10_11 = '10_11';
    case RANGE_12_PLUS = '12_plus';
}
