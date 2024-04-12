<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CheckValidDomain implements Rule
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
        return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $value) //valid chars check
            && preg_match("/^.{1,253}$/", $value) //overall length check
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $value) );
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be a valid domain.';
    }
}
