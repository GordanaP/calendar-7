<?php

namespace App\Services\Utilities;

use App\BusinessDay;
use App\Services\Utilities\AppCarbon;
use Illuminate\Support\Facades\App;

class BusinessSchedule extends AppCarbon
{
    /**
     * The earliest business open time.
     */
    public function theEarliestOpen(): string
    {
        return BusinessDay::min('open');
    }

    /**
     * The latest business close time.
     */
    public function theLatestClose(): string
    {
        return BusinessDay::max('close');
    }

    /**
     * The business opening time on a given date.
     *
     * @param  string $date
     */
    public function openingTime($date) : string
    {
        $iso = $this->isoIndex($date);

        return BusinessDay::findByIso($iso)->open;
    }

    /**
     * The business closing time on a given date.
     *
     * @param  string $date
     */
    public function closingTime($date) : string
    {
        $iso = $this->isoIndex($date);

        return BusinessDay::findByIso($iso)->close;
    }

    /**
     * The last appointment start time on a given date.
     *
     * @param  string  $date
     * @param  integer $interval
     */
    public function lastAppStart($date, $interval) : string
    {
        return $this->parse($this->closingTime($date))
            ->subMinutes($interval)
            ->format('H:i');
    }

    /**
     * Determine if the specific time is within business hours on a given date.
     *
     * @param  string  $time
     * @param  string  $date
     */
    public function isBusinessHour($time, $date) : bool
    {
        return $this->inTimeRange($time, $this->openingTime($date), $this->lastAppStart($date));
    }

    /**
     * Determine if the date is a business work day.
     *
     * @param  string  $date
     */
    public function isBusinessDay($date) : bool
    {
       return ! $this->parse($date)->isSunday() &&
       ! App::make('holidays')->isHoliday($date);
    }
}