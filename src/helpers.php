<?php
/**
 * This file is part of Evacuator package.
 *
 * @author Serafim <nesk@xakep.ru>
 * @date 06.04.2016 15:45
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Serafim\Evacuator\Evacuator;


if (!function_exists('rescue')) {
    /**
     * @param Closure $what
     * @return Closure
     */
    function rescue(\Closure $what)
    {
        $evacuator = new Evacuator($what);

        return function ($count = 0) use ($evacuator) {
            return $evacuator->retry($count)->invoke();
        };
    }
}
