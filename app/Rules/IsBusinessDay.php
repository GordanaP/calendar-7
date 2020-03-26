<?php

namespace App\Rules;

use Illuminate\Support\Facades\App;
use Illuminate\Contracts\Validation\Rule;

class IsBusinessDay implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return App::make('business-schedule')->isBusinessDay($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Business is closed on public holidays and Sundays.';
    }
}
