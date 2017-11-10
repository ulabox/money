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
    private function __construct(string $amount, Currency $currency)
    {
        $this->amount = $amount;
        $this->currency = $currency;
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
    public static function __callStatic(string $method, array $arguments)
    {
        return self::fromAmount($arguments[0], Currency::fromCode($method));
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

        //Properly initialize a bc number
        //@see https://github.com/php/php-src/pull/2746
        return new self(
            is_float($amount) ?
                number_format($amount, self::SCALE, '.', '') :
                bcadd($amount, '0', self::SCALE),
            $currency
        );
    }

    /**
     * Returns a new Money instance based on the current one
     *
     * @param string $amount
     *
     * @return Money
     */
    private function newInstance(string $amount)
    {
        return new self($amount, $this->currency);
    }

    /**
     * Returns the value represented by this Money object
     *
     * @return string
     */
    public function amount(): string
    {
        return $this->amount;
    }

    /**
     * Returns the currency of this Money object
     *
     * @return Currency
     */
    public function currency(): Currency
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
    public function add(Money $addend): Money
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
    public function subtract(Money $subtrahend): Money
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
    public function multiplyBy($multiplier): Money
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
    public function divideBy($divisor): Money
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
    public function round($scale = 0): Money
    {
        if (!is_int($scale)) {
            throw new InvalidArgumentException('Scale is not an integer');
        }
        $add = '0.' . str_repeat('0', $scale) . '5';
        if ($this->isNegative()) {
            $add = '-' . $add;
        }
        $newAmount = bcadd($this->amount, $add, $scale);

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
    public function convertTo(Currency $targetCurrency, $conversionRate): Money
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
     * @return bool
     */
    public function equals(Money $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    /**
     * Checks whether the value represented by this object is greater than the other
     *
     * @param Money $other
     *
     * @return bool
     */
    public function isGreaterThan(Money $other): bool
    {
        return $this->compareTo($other) === 1;
    }

    /**
     * @param Money $other
     *
     * @return bool
     */
    public function isGreaterThanOrEqualTo(Money $other): bool
    {
        return $this->compareTo($other) >= 0;
    }

    /**
     * Checks whether the value represented by this object is less than the other
     *
     * @param Money $other
     *
     * @return bool
     */
    public function isLessThan(Money $other): bool
    {
        return $this->compareTo($other) === -1;
    }

    /**
     * @param Money $other
     *
     * @return bool
     */
    public function isLessThanOrEqualTo(Money $other): bool
    {
        return $this->compareTo($other) <= 0;
    }

    /**
     * Checks if the value represented by this object is zero
     *
     * @return bool
     */
    public function isZero(): bool
    {
        return $this->compareTo0() === 0;
    }

    /**
     * Checks if the value represented by this object is positive
     *
     * @return bool
     */
    public function isPositive(): bool
    {
        return $this->compareTo0() === 1;
    }

    /**
     * Checks if the value represented by this object is negative
     *
     * @return bool
     */
    public function isNegative(): bool
    {
        return $this->compareTo0() === -1;
    }

    /**
     * Checks whether a Money has the same Currency as this
     *
     * @param Money $other
     *
     * @return bool
     */
    public function hasSameCurrencyAs(Money $other): bool
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
    private function compareTo(Money $other): int
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
    private function compareTo0(): int
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
