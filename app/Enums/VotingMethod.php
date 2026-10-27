<?php

namespace App\Enums;

enum VotingMethod: string
{
    case Single = 'single';
    case Multiple = 'multiple';
    case Ranking = 'ranking';
}
