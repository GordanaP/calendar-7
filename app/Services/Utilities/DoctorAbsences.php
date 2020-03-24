<?php

namespace App\Services\Utilities;

use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use App\Services\Utilities\AppCarbon;

class DoctorAbsences extends AppCarbon
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
    public function setDoctor($doctor): DoctorAbsences
    {
        $this->doctor = $doctor;

        return $this;
    }

    /**
     * Determine if the given date is the doctor's absence day.
     *
     * @param  string  $date
     */
    public function isAbsenceDay($date): bool
    {
        return $this->all()->pluck('dates')->flatten()->contains($date);
    }

    /**
     * All absences.
     */
    public function all(): Collection
    {
        return $this->doctor->absences->map(function($absence) {
            $start = $this->createFromFormat('Y-m-d', $absence->day->start_at);
            $duration = $absence->day->duration;
            $absence_type = $absence->type;

            $absencePeriod = $this->periodExpandedForHolidays($start, $duration);

            $absenceDates = $this->absenceBusinessDays($absencePeriod);

            return $this->absenceCollection($absenceDates, $absence_type);
        });
    }

    /**
     * Create a collection of absences - dates & types.
     *
     * @param  Illuminate\Support\Collection $dates
     * @param  string $absence_type
     */
    public function absenceCollection($dates, $absence_type): Collection
    {
        return collect([
            'dates' => $dates,
            'type' => $absence_type
        ]);
    }

    /**
     * Absence including business days only.
     *
     * @param  \Illuminate\Support\Collection $absence
     */
    public function absenceBusinessDays($absence): Collection
    {
        return collect($absence)->filter(function($date) {
            $formatted = $date->format('Y-m-d');
            return ! $date->isWeekend() && ! App::make('holidays')->isHoliday($formatted);
        })->map(function($date){
            return $date->format('Y-m-d');
        })->flatten();
    }

    /**
     * The period excluding holidays.
     *
     * @param  \Carbon\Carbon $start
     * @param  integer $duration
     */
    public function periodExpandedForHolidays($start, $duration): CarbonPeriod
    {
        $end = $start->copy()->addWeekdays($duration-1);

        for ($i=0; $i < $duration; $i++) {
            $date = $start->copy()->addWeekdays($i)->format('Y-m-d');

            if(App::make('holidays')->isHoliday($date)) {
                $end->addWeekday();
            }
        }

        return CarbonPeriod::create($start->copy(), $end);
    }
}