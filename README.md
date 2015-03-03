# Money

[![Build Status](https://api.travis-ci.org/ulabox/money.png?branch=master)](http://travis-ci.org/ulabox/money)

PHP 5.4+ Money library using BCMath, inspired by the works of [Mathias Verraes][2] and [Commerce Guys][3]

## Motivation

Representing monetary values using floats is bad, you can lose precicion an get weird results. It's better to use a Money value object that uses integers or BCMath. This library uses the latter.

### Why another Money library? There are already tons out there!

Yup, but most of them use integers as the internal money representation and that doesn't fit our needs! Using integers is fine in most cases and much more performant, but there are times when you need sub-unit calculations. For example, gas pricing here in Spain is calculated using tenths of an Euro cent or 1.001 €. VAT calculations can also make use of this extra precision an Arbitrary Precision library like BCMath provides. We are an ecommerce and we need that extra precision, especially when calculating discounts.

### Why BCMath and not GMP?

While the GMP library has better performance, its implementation in PHP lacks decimal arithmetic, it can only deal with integers. There's a [proposal][4] to add the decimal implementation to the PHP extension but that's still a work in progress.

## Installation

Install the library using [composer][1]. Just run the following command

```sh
$ composer require ulabox/money:@stable
```

## Usage

```php
<?php

use Money\Money;

$tenEUR = Money::EUR('10.00');
$twentyEUR = $tenEUR->add($tenEUR);
$fifteenEUR = $twentyEUR->subtract(Money::EUR('5.00'));

assert($tenEUR->isLessThan($twentyEUR));
assert($fifteenEUR->isGreaterThan($tenEUR));
assert($fifteenEUR->equals(Money::EUR('15.00')));

```
## Integration with Doctrine 2.5

Starting from version 2.5 Doctrine supports working with Value Objects in what they call [Embeddables][5]. Bear in mind that this Money object has also a Currency VO inside, this is an embeddable inside an embeddable. Doctrine should work just fine with nested embeddables.

We suggest mapping the `amount` field to a `decimal` type. Decimal fields don't lose precision and are converted to a string type in PHP by Doctrine, exactly what we need when working with BCMath. The currency `code` field should be mapped to a `string` type. This is an example schema of a `Product` entity that has a `price` Money field.

```
Product:
  type: entity
  embedded:
    price:
      class: Money\Money

Money\Money:
  type: embeddable
  fields:
    amount: { type: decimal, precision: 10, scale: 2 }
  embedded:
    currency:
      class: Money\Currency

Money\Currency
  type: embeddable
  fields:
    code: { type: string, length: 3 }

```
 
## Disclaimer

We aim to keep this library as simple as possible. That means we don't see the need of having plenty of calculation operations inside the Money class, keep that in mind if you plan to spend some valuable time in a PR! But of course, this can change as we see fit :p

We don't check the currency code against a list of valid ISO codes as we have some fake currencies in our system that use custom currency codes.

[1]: https://getcomposer.org
[2]: https://github.com/mathiasverraes/money
[3]: https://github.com/commerceguys/pricing
[4]: https://wiki.php.net/rfc/gmp-floating-point
[5]: http://doctrine-orm.readthedocs.org/en/latest/tutorials/embeddables.html
