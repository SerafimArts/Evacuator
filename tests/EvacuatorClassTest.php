<?php declare(strict_types = 1);
/**
 * This file is part of Evacuator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Serafim\Evacuator\Test;

use Serafim\Evacuator\Evacuator;

/**
 * Class EvacuatorClassTestCase
 * @package Serafim\Evacuator\Test
 */
class EvacuatorClassTest extends TestCase
{
    /**
     * @return void
     */
    public function testNoRetries()
    {
        $hasErrors = false;
        $counter = 0;

        $rescue = new Evacuator(function () use (&$counter) {
            $counter++;
            throw new \RuntimeException('Evacuator');
        });

        try {
            $rescue->invoke();
        } catch (\Throwable $e) {
            $hasErrors = $e;
        }

        $this->assertNotNull($hasErrors);
        $this->assertInstanceOf(\RuntimeException::class, $hasErrors);
        $this->assertEquals('Evacuator', $hasErrors->getMessage());
        $this->assertEquals(1, $counter);
    }

    /**
     * @return void
     */
    public function test10Reties()
    {
        $hasErrors = false;
        $counter = 0;

        $rescue = (new Evacuator(function () use (&$counter) {
            $counter++;
            throw new \RuntimeException('Evacuator');
        }))
            ->retries(10);

        try {
            $rescue->invoke();
        } catch (\Throwable $e) {
            $hasErrors = $e;
        }


        $this->assertNotNull($hasErrors);
        $this->assertInstanceOf(\RuntimeException::class, $hasErrors);
        $this->assertEquals('Evacuator', $hasErrors->getMessage());
        $this->assertEquals(11, $counter);
    }


    /**
     * @return void
     */
    public function testNoErrors()
    {
        $counter = $initial = random_int(1, 9999);
        $result = (new Evacuator(function () use (&$counter) {
            return $counter++;
        }))
            ->retries(10)
            ->invoke();

        $this->assertEquals($initial, $result);
    }

    /**
     * @return void
     */
    public function testCatchRuntimeErrors()
    {
        $counter = $initial = random_int(1, 9999);

        (new Evacuator(function () use (&$counter) {
            throw (++$counter > 10) ? new \RuntimeException() : new \LogicException();
        }))
            ->retries(20)
            ->catch(function (\RuntimeException $e) {
                $this->assertInstanceOf(\RuntimeException::class, $e);
            })
            ->invoke();

        $this->assertEquals($initial + 21, $counter);
    }

    /**
     * @return void
     */
    public function testPositiveFinally()
    {
        $result = null;

        (new Evacuator(function () {
            return 23;
        }))
            ->finally(function ($value) use (&$result) {
                $result = $value;
            })
            ->invoke();

        $this->assertEquals(23, $result);
    }

    /**
     * @return void
     */
    public function testPositiveFinallyWithError()
    {
        $result = null;
        $counter = 0;

        (new Evacuator(function () use (&$counter) {
            if (++$counter < 10) {
                throw new \LogicException('Error');
            }

            return $counter;
        }))
            ->retries(10)
            ->finally(function ($value) use (&$result) {
                $result = $value;
            })
            ->invoke();

        $this->assertEquals(10, $counter);
        $this->assertEquals(10, $result);
    }

    /**
     * @return void
     */
    public function testNegativeFinallyWithError()
    {
        $result = null;

        (new Evacuator(function () {
            throw new \LogicException('Error');
        }))
            ->retries(10)
            ->finally(function ($value) use (&$result) {
                $result = $value;
            })
            ->invoke();

        $this->assertInstanceOf(\LogicException::class, $result);
    }

    /**
     * @return void
     */
    public function testSuccessfullResponse()
    {
        $result = (new Evacuator(function () {
            return 0xDEADBEEF;
        }))
            ->invoke();

        $this->assertEquals(0xDEADBEEF, $result);
    }

    /**
     * @return void
     */
    public function testSuccessfullResponseUsingFinally()
    {
        $result = (new Evacuator(function () {
            return 23;
        }))
            ->finally(function ($value) {
                return $value + 19;
            })
            ->invoke();

        $this->assertEquals(42, $result);
    }

    /**
     * @return void
     */
    public function testSuccessfullResponseWithExceptions()
    {
        $counter = 0;
        $result = (new Evacuator(function () use (&$counter) {
            if ($counter++ < 10) {
                throw new \LogicException('Error');
            }

            return 42;
        }))
            ->retries(10)
            ->invoke();

        $this->assertEquals(42, $result);
    }

    /**
     * @return void
     */
    public function testSuccessfullResponseUsingCatch()
    {
        $result = (new Evacuator(function () {
            throw new \LogicException('Error');
        }))
            ->catch(function (\RuntimeException $e) {
                return 23;
            })
            ->catch(function ($error) {
                return $error instanceof \LogicException ? 42 : 23;
            })
            ->retries(10)
            ->invoke();

        $this->assertEquals(42, $result);
    }

    /**
     * @return void
     */
    public function testOnEveryError()
    {
        $counter = 0;

        $result = (new Evacuator(function () {
            throw new \LogicException('Error');
        }))
            ->onError(function (\RuntimeException $e) use (&$counter) { // Must not be call
                --$counter;
            })
            ->onError(function (\LogicException $e) use (&$counter) { // Must be calls
                $counter += 1;
            })
            ->onError(function ($e) use (&$counter) { // Must not be calls (priority)
                $counter += 2;
            })
            ->catch(function (\LogicException $e) {
                return 23;
            })
            ->retries(10)
            ->invoke();

        $this->assertEquals($result, 23);
        $this->assertEquals(11, $counter);
    }

    /**
     * @return void
     */
    public function testInfinityRetriesError()
    {
        $counter = 0;

        $result = (new Evacuator(function () use (&$counter) {
            if (++$counter < 9999) {
                throw new \LogicException('Error');
            }
            return 0xDEADBEEF;
        }))
            ->retries(Evacuator::INFINITY_RETRIES)
            ->invoke();

        $this->assertEquals($result, 0xDEADBEEF);
    }
}
