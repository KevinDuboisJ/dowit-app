<?php

namespace App\Enums;

enum TaskPlannerEvaluationResultEnum
{
    case ShouldTrigger;
    case Deactivated;
    case Rescheduled;
}
