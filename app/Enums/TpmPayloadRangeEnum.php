<?php

namespace App\Enums;

enum TpmPayloadRangeEnum: string
{
    case RANGE_INFERIOR_OR_EQUAL_TO_1 = 'inferior_or_equal_to_1';
    case RANGE_1_3 = '1_3';
    case RANGE_3_5 = '3_5';
    case RANGE_5_8 = '5_8';
    case RANGE_9_12 = '9_12';
    case RANGE_12_15 = '12_15';
    case RANGE_15_PLUS = '15_plus';
}
