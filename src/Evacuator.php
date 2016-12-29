<?php declare(strict_types = 1);
/**
 * This file is part of Evacuator package.
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
    /**
     * @var string
     * @internal
     */
    const CATCH_ALL_EXCEPTIONS = '*';

    /**
     * @var int
     */
    const INFINITY_RETRIES = -100;

    /**
     * @var \Closure
     */
    private $context;

    /**
     * @var int
     */
    private $retries = 0;

    /**
     * @var array|\Closure[]
     */
    private $catches = [];

    /**
     * @var null|\Closure
     */
    private $then;

    /**
     * @var array|\Closure
     */
    private $everyError = [];

    /**
     * Evacuator constructor.
     * @param \Closure $context
     */
    public function __construct(\Closure $context)
    {
        $this->context = $context;
    }

    /**
     * @param array ...$args
     * @return mixed
     * @throws \Throwable
     */
    public function __invoke(...$args)
    {
        return $this->invoke(...$args);
    }

    /**
     * @param int $count
     * @return Evacuator
     * @throws \InvalidArgumentException
     */
    public function retries(int $count): Evacuator
    {
        if ($count < 0 && $count !== static::INFINITY_RETRIES) {
            throw new \InvalidArgumentException('Retries count must be greater than 0');
        }

        $this->retries = $count;

        return $this;
    }

    /**
     * @param \Closure $then
     * @return Evacuator|$this
     * @throws \InvalidArgumentException
     */
    public function catch(\Closure $then): Evacuator
    {
        $exceptionClass = $this->resolveTypeHint($then, \Throwable::class);

        $this->catches[$exceptionClass] = $then;

        return $this;
    }

    /**
     * @param \Closure $then
     * @return Evacuator
     * @throws \InvalidArgumentException
     */
    public function onError(\Closure $then): Evacuator
    {
        $exceptionClass = $this->resolveTypeHint($then, \Throwable::class);

        $this->everyError[$exceptionClass] = $then;

        return $this;
    }

    /**
     * @param \Closure $closure
     * @param string $instanceOf
     * @return string
     * @throws \InvalidArgumentException
     */
    private function resolveTypeHint(\Closure $closure, string $instanceOf): string
    {
        $parameters = (new \ReflectionFunction($closure))->getParameters();

        // Callback has ony one argument
        if (1 !== count($parameters)) {
            throw new \InvalidArgumentException(
                'Closure argument of catch(...) method required ' . (count($parameters) > 1 ? 'only ' :'') .
                '1 parameter ' . count($parameters) . ' given'
            );
        }

        // Callback has no type hints
        if (null === reset($parameters)->getType()) {
            return static::CATCH_ALL_EXCEPTIONS;
        }

        $typeHintClass = reset($parameters)->getClass();

        // Callback has primitive type hint
        if (null === $typeHintClass) {
            throw new \InvalidArgumentException(
                'Closure argument of catch(...) method type hint can not be a primitive'
            );
        }

        if (!($typeHintClass->newInstanceWithoutConstructor() instanceof $instanceOf)) {
            throw new \InvalidArgumentException(
                'Closure argument of catch(...) method type hint must be instance of ' . $instanceOf .
                    ', ' . $typeHintClass->name . ' given'
            );
        }

        return $typeHintClass->name;
    }

    /**
     * @param array ...$args
     * @return mixed
     * @throws \Throwable
     */
    public function invoke(...$args)
    {
        $result = null;
        $error  = null;

        try {
            $result = $this->callClosure(...$args);
        } catch (\Throwable $e) {
            $error = $e;
        }

        if ($this->then) {
            return ($this->then)($result ?? $error);
        }

        if ($error !== null) {
            throw $error;
        }

        return $result;
    }

    /**
     * @param array ...$args
     * @return mixed
     * @throws \Throwable
     */
    private function callClosure(...$args)
    {
        while (
            $this->retries === static::INFINITY_RETRIES ||
            ($this->retries-- + 1) > 0
        ) {
            try {
                return ($this->context)(...$args);
            } catch (\Throwable $e) {
                $this->throw($e, $this->everyError);

                if ($this->willBeThrows()) {
                    return $this->throw($e, $this->catches, true);
                }
            }
        }

        return null;
    }

    /**
     * @return bool
     * @throws \Throwable
     */
    private function willBeThrows(): bool
    {
        return $this->retries !== static::INFINITY_RETRIES && $this->retries < 0;
    }

    /**
     * @param \Throwable $e
     * @param array $callbacks
     * @param bool $throwAfter
     * @return mixed
     * @throws \Throwable
     */
    private function throw(\Throwable $e, array $callbacks, bool $throwAfter = false)
    {
        foreach ($callbacks as $name => $callback) {
            if ($e instanceof $name || $name === static::CATCH_ALL_EXCEPTIONS) {
                return $callback($e);
            }
        }

        if ($throwAfter) {
            throw $e;
        }

        return null;
    }

    /**
     * @param \Closure $then
     * @return Evacuator|$this
     */
    public function finally(\Closure $then): Evacuator
    {
        $this->then = $then;

        return $this;
    }
}
