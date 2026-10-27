<?php

namespace App\Enums;

enum PollPhase: string
{
    case Scheduled = 'scheduled';
    case Suggestions = 'suggestions';
    case Voting = 'voting';
    case Closed = 'closed';
}
