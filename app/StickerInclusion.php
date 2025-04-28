<?php

namespace App;

enum StickerInclusion: string
{
    case INCLUDE = 'include';
    case HIDE = 'hide';
    case DYNAMIC = 'dynamic';
}
