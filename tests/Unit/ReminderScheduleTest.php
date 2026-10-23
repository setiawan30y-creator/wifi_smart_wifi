<?php

namespace Tests\Unit;

use App\Services\Billing\ReminderSchedule;
use PHPUnit\Framework\TestCase;

class ReminderScheduleTest extends TestCase
{
    public function test_legacy_settings_become_consecutive_after_days(): void
    {
        $schedule = ReminderSchedule::fromSettings([
            'billing_overdue_wa_enabled' => '1',
            'billing_overdue_days_after_due' => '2',
            'billing_overdue_max_reminders' => '3',
        ]);

        $this->assertTrue($schedule->afterEnabled);
        $this->assertSame([2, 3, 4], $schedule->afterDays);
        $this->assertFalse($schedule->beforeEnabled);
        $this->assertSame('08:00', $schedule->time);
    }

    public function test_parses_and_sorts_days(): void
    {
        $this->assertSame([0, 3, 1], ReminderSchedule::parseDays('[0,"3",1,3,-1,"x"]', 31));
        $this->assertSame([1, 5], ReminderSchedule::parseDays('1, 5, 0', 120, 1));

        $schedule = ReminderSchedule::fromSettings([
            'billing_reminder_before_enabled' => '1',
            'billing_reminder_before_days' => '[0,3,1]',
            'billing_overdue_wa_enabled' => '1',
            'billing_reminder_after_days' => '[7,1,3]',
            'billing_reminder_time' => '09:30',
        ]);

        $this->assertSame([3, 1, 0], $schedule->beforeDays);
        $this->assertSame([1, 3, 7], $schedule->afterDays);
        $this->assertSame('09:30', $schedule->time);
    }

    public function test_stage_selection(): void
    {
        $schedule = ReminderSchedule::fromSettings([
            'billing_reminder_before_enabled' => '1',
            'billing_reminder_before_days' => '[3,1,0]',
            'billing_overdue_wa_enabled' => '1',
            'billing_reminder_after_days' => '[1,3,7]',
        ]);

        $this->assertNull($schedule->beforeStage(5));
        $this->assertSame(3, $schedule->beforeStage(3));
        $this->assertSame(3, $schedule->beforeStage(2));
        $this->assertSame(1, $schedule->beforeStage(1));
        $this->assertSame(0, $schedule->beforeStage(0));

        $this->assertNull($schedule->afterStage(0));
        $this->assertSame(1, $schedule->afterStage(2));
        $this->assertSame(3, $schedule->afterStage(6));
        $this->assertSame(7, $schedule->afterStage(40));
    }

    public function test_empty_day_list_disables_section(): void
    {
        $schedule = ReminderSchedule::fromSettings([
            'billing_reminder_before_enabled' => '1',
            'billing_reminder_before_days' => '[]',
        ]);

        $this->assertFalse($schedule->beforeEnabled);
    }
}
