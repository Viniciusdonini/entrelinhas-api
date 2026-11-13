<?php

namespace App\Enums;

enum BookStatus: string
{
    case WantToRead = 'wantToRead';
    case Reading = 'reading';
    case Finished = 'finished';
}
