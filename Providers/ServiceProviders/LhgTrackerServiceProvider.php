<?php

namespace KimaiPlugin\LhgTrackerBundle\Providers\ServiceProviders;

use PhpParser\Node\Expr\Cast\Double;

class LhgTrackerServiceProvider{
    const CUSTOM_FIELD_NAME = "allow_over_budget_tracking_on_project";
    const DAILY_WORK_TIMECUSTOM_FIELD_NAME = "daily_work_time_limit";
    const DAILY_WORK_TIMECUSTOM_FIELD_VALUE = 8;

    public static function calculateProjectSpentMoney(): float{
        return 0.00;
    }

    public static function calculateProjectSpentTime(): float{
        return 0.00;
    }
}