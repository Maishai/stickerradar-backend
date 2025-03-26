<?php

namespace App;

enum State: string
{
    case EXISTS = "exists";
    case COVERED = "covered";
    case REMOVED = "removed";
}
