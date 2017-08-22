<?php

/**
 * This file is part of the Ulabox Money library.
 *
 * Copyright (c) 2011-2015 Ulabox SL
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Money;

final class Money
{
    /**
     * The scale used in BCMath calculations
     */
    const SCALE = 4;

    /**
     * The money amount
     *
     * @var string
     */
    protected $amount;

    /**
     * The amount currency
     *
     * @var Currency
     */
    protected $currency;

    /**
     * @param string $amount Amount, expressed as a string (eg '10.00')
     * @param Currency $currency
     *
     * @throws InvalidArgumentException If amount is not a numeric string value
     */
     private function __construct($amount, Currency $currency)
     {
         $this->setAmount($amount);
         $this->currency = $currency;
     }

     /**
      * @param string $amount
      */
     private function setAmount($amount)
     {
         $hasDecimals = (bool) preg_match('/\./', $amount);
         $this->amount = ($hasDecimals) ? rtrim($amount, "0") : $amount;
     }

    /**
     * Convenience factory method for a Money object
     *
     * <code>
     * $fiveDollar = Money::USD(500);
     * </code>
     *
     * @param string $method
     * @param array $arguments
     *
     * @return Money
     */
    public static function __callStatic($method, $arguments)
    {
        self::assertNumeric($arguments[0]);

        return new self((string) $arguments[0], Currency::fromCode($method));
    }

    /**
     * Creates a Money object from its amount and currency
     *
     * @param numeric $amount
     * @param Currency $currency
     *
     * @return Money
     */
    public static function fromAmount($amount, Currency $currency)
    {
        self::assertNumeric($amount);

        return new self((string) $amount, $currency);
    }

    /**
     * Returns a new Money instance based on the current one
     *
     * @param string $amount
     *
     * @return Money
     */
    private function newInstance($amount)
    {
        return new self($amount, $this->currency);
    }

    /**
     * Returns the value represented by this Money object
     *
     * @return string
     */
    public function amount()
    {
        return $this->amount;
    }

    /**
     * Returns the currency of this Money object
     *
     * @return Currency
     */
    public function currency()
    {
        return $this->currency;
    }

    /**
     * Returns a new Money object that represents
     * the sum of this and another Money object
     *
     * @param Money $addend
     *
     * @return Money
     */
    public function add(Money $addend)
    {
        $this->assertSameCurrencyAs($addend);

        $amount = bcadd($this->amount, $addend->amount, self::SCALE);

        return $this->newInstance($amount);
    }

    /**
     * Returns a new Money object that represents
     * the difference of this and another Money object
     *
     * @param Money $subtrahend
     *
     * @return Money
     */
    public function subtract(Money $subtrahend)
    {
        $this->assertSameCurrencyAs($subtrahend);

        $amount = bcsub($this->amount, $subtrahend->amount, self::SCALE);

        return $this->newInstance($amount);
    }

    /**
     * Returns a new Money object that represents
     * the multiplied value by the given factor
     *
     * @param numeric $multiplier
     *
     * @return Money
     */
    public function multiplyBy($multiplier)
    {
        self::assertNumeric($multiplier);

        $amount = bcmul($this->amount, (string) $multiplier, self::SCALE);

        return $this->newInstance($amount);
    }

    /**
     * Returns a new Money object that represents
     * the divided value by the given factor
     *
     * @param numeric $divisor
     *
     * @return Money
     * @throws InvalidArgumentException In case divisor is zero.
     */
    public function divideBy($divisor)
    {
        self::assertNumeric($divisor);
        if (0 === bccomp((string) $divisor, '', self::SCALE)) {
            throw new InvalidArgumentException('Divisor cannot be 0.');
        }

        $amount = bcdiv($this->amount, (string) $divisor, self::SCALE);

        return $this->newInstance($amount);
    }

    /**
     * Rounds this Money to another scale
     *
     * @param integer $scale
     *
     * @return Money
     */
    public function round($scale = 0)
    {
        if (!is_int($scale)) {
            throw new InvalidArgumentException('Scale is not an integer');
        }
        $newAmount = sprintf('%.'.$scale.'f', $this->amount());

        return $this->newInstance($newAmount);
    }

    /**
     * Converts the currency of this Money object to
     * a given target currency with a given conversion rate
     *
     * @param Currency $targetCurrency
     * @param numeric $conversionRate
     *
     * @return Money
     */
    public function convertTo(Currency $targetCurrency, $conversionRate)
    {
        self::assertNumeric($conversionRate);

        $amount = bcmul($this->amount, (string) $conversionRate, self::SCALE);

        return new Money($amount, $targetCurrency);
    }

    /**
     * Checks whether the value represented by this object equals to the other
     *
     * @param Money $other
     *
     * @return boolean
     */
    public function equals(Money $other)
    {
        return $this->compareTo($other) === 0;
    }

    /**
     * Checks whether the value represented by this object is greater than the other
     *
     * @param Money $other
     *
     * @return boolean
     */
    public function isGreaterThan(Money $other)
    {
        return $this->compareTo($other) === 1;
    }

    /**
     * @param Money $other
     *
     * @return bool
     */
    public function isGreaterThanOrEqualTo(Money $other)
    {
        return $this->compareTo($other) >= 0;
    }

    /**
     * Checks whether the value represented by this object is less than the other
     *
     * @param Money $other
     *
     * @return boolean
     */
    public function isLessThan(Money $other)
    {
        return $this->compareTo($other) === -1;
    }

    /**
     * @param Money $other
     *
     * @return bool
     */
    public function isLessThanOrEqualTo(Money $other)
    {
        return $this->compareTo($other) <= 0;
    }

    /**
     * Checks if the value represented by this object is zero
     *
     * @return boolean
     */
    public function isZero()
    {
        return $this->compareTo0() === 0;
    }

    /**
     * Checks if the value represented by this object is positive
     *
     * @return boolean
     */
    public function isPositive()
    {
        return $this->compareTo0() === 1;
    }

    /**
     * Checks if the value represented by this object is negative
     *
     * @return boolean
     */
    public function isNegative()
    {
        return $this->compareTo0() === -1;
    }

    /**
     * Checks whether a Money has the same Currency as this
     *
     * @param Money $other
     *
     * @return boolean
     */
    public function hasSameCurrencyAs(Money $other)
    {
        return $this->currency->equals($other->currency);
    }

    /**
     * Returns an integer less than, equal to, or greater than zero
     * if the value of this object is considered to be respectively
     * less than, equal to, or greater than the other
     *
     * @param Money $other
     *
     * @return int
     */
    private function compareTo(Money $other)
    {
        $this->assertSameCurrencyAs($other);

        return bccomp($this->amount, $other->amount, self::SCALE);
    }

    /**
     * Returns an integer less than, equal to, or greater than zero
     * if the value of this object is considered to be respectively
     * less than, equal to, or greater than 0
     *
     * @param Money $other
     *
     * @return int
     */
    private function compareTo0()
    {
        return bccomp($this->amount, '', self::SCALE);
    }

    /**
     * Asserts that a Money has the same currency as this
     *
     * @param Money $other
     *
     * @throws InvalidArgumentException If $other has a different currency
     */
    private function assertSameCurrencyAs(Money $other)
    {
        if (!$this->hasSameCurrencyAs($other)) {
            throw new InvalidArgumentException('Currencies must be identical');
        }
    }

    /**
     * Asserts that a value is a valid numeric string
     *
     * @param numeric $value
     *
     * @throws InvalidArgumentException If $other has a different currency
     */
    private static function assertNumeric($value)
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Amount must be a valid numeric value');
        }
    }
}
