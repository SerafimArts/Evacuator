<?php declare(strict_types=1);
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
     * @return mixed
     */
    function rescue(\Closure $what)
    {
        return (new Evacuator($what))
            ->retries(Evacuator::INFINITY_RETRIES)
            ->invoke();
    }
}

if (!function_exists('repeat')) {
    /**
     * @param int $repeatsCount
     * @param Closure $what
     * @return mixed
     */
    function repeat_and_rescue(int $repeatsCount, \Closure $what)
    {
        return (new Evacuator($what))
            ->retries(abs($repeatsCount))
            ->invoke();
    }
}
