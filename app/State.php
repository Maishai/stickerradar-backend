<?php

namespace App;

enum State: string
{
    case EXISTS = 'exists';
    case COVERED = 'covered';
    case PARTIALLY_REMOVED = 'partially_removed';
    case REMOVED = 'removed';
}
