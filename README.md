Evacuator
=========

He trying to save your code, if it is broken :3

## Usage

```php
$evacuator = rescue(function () { // What we are trying to save
    echo 23 . "\n";

    throw new Exception('Ooooups =(');
});

$evacuator(2); // How much retries

/* ===============
 *     OUTPUT
 * ===============
 *
 * 23
 * 23 // first retry 
 * 23 // second retry
 *  
 *  Fatal error: Uncaught Exception: Ooooups =( in
 */
```

## Installation

`composer require serafim/evacuator`

## Extended usage

```php
use Serafim\Evacuator\Evacuator;

(new Evacuator(function() {

    // Your a very important piece of code

}))

    ->retry(100500) // If code throws an exception - retries 100500 times
    ->retry(Evacuator::INFINITY_RETRIES) // or until the cancer is not on the mountain whistles...
    
    ->catch(function(Throwable $e) {
        // Run this code after every attempt.
    })
    
    ->finally(function() {
        // Run code after all attempts. Even when trying to repeat the action was not been made.
    })
    
    ->invoke(); // Just run your very important code
```

Enjoy! :3
