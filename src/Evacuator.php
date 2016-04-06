<?php
/**
 * This file is part of Evacuator package.
 *
 * @author Serafim <nesk@xakep.ru>
 * @date 06.04.2016 15:22
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
    public function catch (\Closure $callback)
    {
        $this->catchable = $callback;

        return $this;
    }

    /**
     * @param \Closure $callback
     * @return $this
     */
    public function finally(\Closure $callback)
    {
        $this->finalizable = $callback;

        return $this;
    }

    /**
     * @param int $count
     * @return $this|Evacuator
     */
    public function retry(int $count) : Evacuator
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
                $result = ($this->action)(...$args);
                return $result;

            } catch (\Throwable $e) {
                $exception = $e;
                if ($this->catchable !== null) {
                    ($this->catchable)($e);
                }

            }
        } while (
            $this->retries === static::INFINITY_RETRIES ||
            $current++ < $this->retries
        );


        if ($this->finalizable !== null) {
            ($this->finalizable)($exception);
        }

        if ($exception !== null) {
            throw $exception;
        }
    }
}
