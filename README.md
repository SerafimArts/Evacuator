Evacuator 2.0
=============

[![Build Status](https://travis-ci.org/SerafimArts/Evacuator.svg?branch=master)](https://travis-ci.org/SerafimArts/Evacuator)
[![Code Quality](https://scrutinizer-ci.com/g/SerafimArts/Evacuator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/SerafimArts/Evacuator/inspections)

He trying to save your code, if it is broken :3

## Installation

`composer require serafim/evacuator`

> Link to [Packagist](https://packagist.org/packages/serafim/evacuator)

## Usage example

```php
// What we are trying to keep safe?
$result = rescue(function () { 
    if (random_int(0, 9999) > 1) {
        throw new \Exception('Ooooups =(');
    }
    
    return 23;
});

var_dump($result); // int(23)
```

## Advanced usage

```php
use Serafim\Evacuator\Evacuator;

$result = (new Evacuator(function() {

    // Your a very important piece of code

}))

    // Code throws an exception after 100500 call retries 
    ->retry(100500) 
    
    // or until the cancer is not on the mountain whistles...
    ->retry(Evacuator::INFINITY_RETRIES) 
    
    // But if you want catch exception
    ->catch(function (Throwable $e) {
        return 'Something went wrong =('; // Will be returns into $result
    })
    
    ->finally(function ($errorOrResult) {
        // Run this code after all attempts.
        // $errorOrResult can be error (if evacuator cant keep safe your code) or result value
    })
    
    ->onError(function ($error) {
        // Run this code after every error
    })
    
    ->invoke(); // Just run your very important code
```

## Catching strategy

```php
$result = (new Evacuator(function() {
    throw new \LogicException('Error');
}))

    ->catch(function (\RuntimeException $e) {
        // I am alone and never be use ='( 
    })
    // ->onError(function (\RuntimeException $e) {})
    
    ->catch(function (\LogicException $e) {
        // Yay! I will calling because Im a LogicException! :D
    })
    // ->onError(function (\LogicException $e) {})
    
    ->invoke();
```

Enjoy! :3
