<?php
/**
 * This file is part of Evacuator package.
 *
 * @author Serafim <nesk@xakep.ru>
 * @date 05.05.2016 11:16
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Serafim\Evacuator;

/**
 * Class Evacuator
 * @package Serafim\Evacuator
 */
class Evacuator
{
    const INFINITY_RETRIES = -1;

    /**
     * @var \Closure
     */
    private $action;

    /**
     * @var int
     */
    private $retries = 0;

    /**
     * @var null|\Closure
     */
    private $catchable = null;

    /**
     * @var null|\Closure
     */
    private $finalizable = null;

    /**
     * Evacuator constructor.
     * @param \Closure $action
     */
    public function __construct(\Closure $action)
    {
        $this->action = $action;
    }

    /**
     * @param \Closure $callback
     * @return $this
     */
    public function error (\Closure $callback)
    {
        $this->catchable = $callback;

        return $this;
    }

    /**
     * @param \Closure $callback
     * @return $this
     */
    public function every(\Closure $callback)
    {
        $this->finalizable = $callback;

        return $this;
    }

    /**
     * @param int $count
     * @return $this|Evacuator
     */
    public function retry($count)
    {
        $this->retries = $count;

        return $this;
    }

    /**
     * @param array ...$args
     * @return \Closure
     * @throws \Throwable
     * @throws null
     */
    public function invoke(...$args)
    {
        $exception = null;
        $current = 0;

        do {
            try {
                $action = $this->action;
                return $action(...$args);

            } catch (\Throwable $e) {
                $exception = $e;

                if ($this->catchable !== null) {
                    $catch = $this->catchable;
                    $catch($e);
                }
            }
        } while (
            $this->retries === static::INFINITY_RETRIES ||
            $current++ < $this->retries
        );


        if ($this->finalizable !== null) {
            $finalize = $this->finalizable;
            $finalize($exception);

        } elseif ($exception !== null) {
            throw $exception;
        }
    }
}
