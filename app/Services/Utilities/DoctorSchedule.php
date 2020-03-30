<?php

namespace App\Services\Utilities;

use App\BusinessDay;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use App\Services\Utilities\AppCarbon;

class DoctorSchedule extends AppCarbon
{
    /**
     * The doctor.
     *
     * @var \App\Doctor
     */
    public $doctor;

    /**
     * Set the doctor.
     *
     * @param \App\Doctor $doctor
     */
    public function setDoctor($doctor): DoctorSchedule
    {
        $this->doctor = $doctor;

        return $this;
    }

    /**
     * Determine if the doctor's scheduling time slot is free on a given day.
     *
     * @param  string  $time
     * @param  string  $date
     */
    public function isNotBookedTimeSlot($slot, $date): ?bool
    {
        $date_time_string = $date .' '.$slot;
        $app_time = $this->fromFormat($date_time_string);

        return $this->isSchedulingTimeSlot($slot, $date)
            ? $this->doctor->appointments->where('start_at', $app_time)->isEmpty()
            : null;
    }

    /**
     * Determine if the time slot is within the doctor's scheduling time slots.
     *
     * @param  string  $slot
     * @param  string  $date
     */
    public function isSchedulingTimeSlot($slot, $date): ?bool
    {
        return $this->schedulingTimeSlots($date)->contains($slot);
    }

    /**
     * Tha doctor's last scheduled time slot on a given day.
     *
     * @param  \App\Doctor  $doctor
     * @param  string  $date
     * @param  integer $interval
     */
    public function lastSchedulingTimeSlot($date): string
    {
        $office_day_end_at = $this->officeDayEndHour($date);
        $app_slot = $this->doctor->app_slot;

        return isset($office_day_end_at) && isset($app_slot)
            ? $this->parse($office_day_end_at)
                ->subMinutes($app_slot)
                ->format('H:i')
            : '';
    }

    /**
     * The disabled time range on the timepicker.
     *
     * @param  string $date
     */
    function timepickerDisableTimeRanges($date): Collection
    {
        // $carbon = App::make('app-carbon');
        // $doctor = $this->doctor;

        return $this->bookedTimeSlots($date)->map(function($slot) {
            return [
                $slot, $this->parse($slot)->addMinutes($this->doctor->app_slot)->format('H:i')
            ];
        });
    }

    /**
     * The doctor's free scheduling time slots on the given date.
     *
     * @param  string $date
     */
    public function bookedTimeSlots($date): Collection
    {
        return $this->schedulingTimeSlots($date)->filter(function ($slot) use($date) {
                $date_time_string = $date .' '. $slot;
                $app_time = $this->fromFormat($date_time_string);

                return $this->doctor->appointments->where('start_at', $app_time)->all();
        })->flatten();
    }

    /**
     * The doctor's scheduling time slots for the given day.
     *
     * @param  string $date
     */
    public function schedulingTimeSlots($date): ?Collection
    {
        $start_at = $this->officeDayStartHour($date);
        $end_at = $this->lastSchedulingTimeSlot($date);
        $minutes = $this->doctor->app_slot;

        $time_slots = $this->minutesIntervals($start_at, $end_at, $minutes);

        return collect($time_slots)->map(function($time_slot) {
            return $time_slot->format('H:i');
        });
    }

    /**
     * Determine if the time is within the doctor's office hours on a given date.
     *
     * @param  string  $time
     * @param  string  $date
     */
    public function isOfficeHour($time, $date): ?bool
    {
        return $this->isOfficeDay($date)
            ? $this->inTimeRange(
                $time,
                $this->officeDayStartHour($date),
                $this->lastSchedulingTimeSlot($date)
            )
            : null;
    }

    /**
     * The doctor's office day end hour.
     *
     * @param  string $date
     */
    public function officeDayEndHour($date): string
    {
        $office_day = $this->findOfficeDay($date);

        return $office_day ? $office_day->hour->end_at : '';
    }

    /**
     * The doctor's office day start hour.
     *
     * @param  string $date
     */
    public function officeDayStartHour($date): string
    {
        $office_day = $this->findOfficeDay($date);

        return $office_day ? $office_day->hour->start_at : '';
    }

    /**
     * The doctor's office hours.
     */
    public function officeHours(): Collection
    {
        return $this->doctor->business_days->map(function($day) {
            return collect([
                'daysOfWeek'=> [$day->iso],
                'startTime' => $day->hour->start_at,
                'endTime' => $day->hour->end_at,
            ]);
        });
    }

    /**
     * Determine if the date is a valid office day.
     *
     * @param  string  $date
     */
    public function isValidOfficeDay($date): bool
    {
        return $this->isValidDate($date) &&
            $this->isEqualOrAfterToday($date) &&
            App::make('business-schedule')->isBusinessDay($date) &&
            $this->isOfficeDay($date) &&
            ! App::make('doctor-absences')->setDoctor($this->doctor)
                ->isAbsenceDay($date);
    }

    /**
     * Determine if the doctor is in the office on the given day.
     *
     * @param  string  $date
     */
    public function isOfficeDay($date): bool
    {
        $date_iso = $this->isoIndex($date);

        return $this->doctor->business_days->map(function($day){
            return $day->iso;
        })->contains($date_iso);
    }

    /**
     * Find the doctor's specific office day.
     *
     * @param  string $date
     */
    public function findOfficeDay($date): ?BusinessDay
    {
        $date_iso = $this->isoIndex($date);

        return $this->doctor->business_days->where('id', $date_iso)->first();
    }

    /**
     * The doctor's office days.
     */
    public function officeDays(): Collection
    {
        return $this->doctor->business_days->map(function($day){
            return $day->iso;
        });
    }
}