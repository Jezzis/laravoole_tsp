<?php

namespace App\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerArrayHelper();
    }

    protected function registerArrayHelper()
    {
        Arr::macro('indexOf', function ($array, $value) {
            $index = -1;
            array_walk($array, function($v, $k) use ($value, &$index) { if ($value == $v)  $index = $k; });
            return $index;
        });

        Arr::macro('removeOf', function (&$array, $value) {
            $index = Arr::indexOf($array, $value);
            if ($index >= 0) unset($array[$index]);
        });

        Arr::macro('swap', function (&$array, $x, $y) {
            $tmp = $array[$x]; $array[$x] = $array[$y]; $array[$y] = $tmp;
        });

        Arr::macro('roll', function ($array) {
            $extremes = [0, count($array) - 1];
            $rand = call_user_func_array('random_int', $extremes);
            return in_array($rand, $extremes) ? $array : array_merge(array_slice($array, 0, $rand), array_slice($array, $rand));
        });
    }
}
