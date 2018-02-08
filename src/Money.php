<?php

/**
 * This file is part of the Ulabox Money library.
 *
 * Copyright (c) 2011-2017 Ulabox SL
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Money;

final class Money
{
    /**
     * The default scale used in BCMath calculations
     */
    public const DEFAULT_SCALE = 4;

    /**
     * The money amount
     *
     * @var string
     */
    private $amount;

    /**
     * The amount currency
     *
     * @var Currency
     */
    private $currency;

    /**
     * The money scale
     *
     * @var int
     */
    private $scale = self::DEFAULT_SCALE;

    /**
     * @param string $amount Amount, expressed as a string (eg '10.00')
     * @param Currency $currency
     * @param int $scale
     *
     * @throws InvalidArgumentException If amount is not a numeric string value
     */
    private function __construct(string $amount, Currency $currency, int $scale)
    {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->scale = $scale;
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
        return self::fromAmount($arguments[0], Currency::fromCode($method), $arguments[1] ?? self::DEFAULT_SCALE);
    }

    /**
     * Creates a Money object from its amount and currency
     *
     * @param int|float|string $amount
     * @param Currency $currency
     * @param int $scale
     *
     * @return Money
     */
    public static function fromAmount($amount, Currency $currency, int $scale = self::DEFAULT_SCALE)
    {
        //Properly initialize a bc number
        //@see https://github.com/php/php-src/pull/2746
        return new self(
            self::initNumeric($amount, $scale),
            $currency,
            $scale
        );
    }

    public static function fromString(string $value): self
    {
        try {
            list($amount, $currencyCode) = explode(':', $value);
            $currency = Currency::fromCode($currencyCode);
            $numberAndDecimals = explode('.', $amount);
            $scale = sizeof($numberAndDecimals) == 1 ? 0 : strlen($numberAndDecimals[1]);

            return new self($amount, $currency, $scale);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('Value should have format "amount[.decimals]:currency"');
        }
    }

    /**
     * Properly init a number for BCMath
     * @param int|float|string $amount
     * @param int $scale
     * @return string
     */
    private static function initNumeric($amount, int $scale): string
    {
        self::assertNumeric($amount);

        return is_float($amount) ?
            number_format($amount, $scale, '.', '') :
            bcadd($amount, '0', $scale)
        ;
    }

    /**
     * Returns a new Money instance based on the current one
     *
     * @param string $amount
     * @param int $scale
     *
     * @return Money
     */
    private function newInstance(string $amount, int $scale)
    {
        return new self($amount, $this->currency, $scale);
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

        $scale = $this->maxScale($addend);
        $amount = bcadd($this->amount, $addend->amount, $scale);

        return $this->newInstance($amount, $scale);
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

        $scale = $this->maxScale($subtrahend);
        $amount = bcsub($this->amount, $subtrahend->amount, $scale);

        return $this->newInstance($amount, $scale);
    }

    /**
     * Returns a new Money object that represents
     * the multiplied value by the given factor
     *
     * @param int|float|string $multiplier
     * @param int $scale
     * @param bool $round
     *
     * @return Money
     */
    public function multiplyBy($multiplier, int $scale = null, bool $round = false): Money
    {
        $scale = null === $scale ? $this->scale : $scale;
        if ($round) {
            ++$scale;
        }
        $multiplier = self::initNumeric($multiplier, $scale);

        $amount = bcmul($this->amount, $multiplier, $scale);
        $money = $this->newInstance($amount, $scale);

        return $round ?
            $money->round(--$scale) :
            $money
        ;
    }

    /**
     * Returns a new Money object that represents
     * the divided value by the given factor
     *
     * @param int|float|string $divisor
     * @param int $scale
     * @param bool $round
     *
     * @return Money
     * @throws InvalidArgumentException In case divisor is zero.
     */
    public function divideBy($divisor, int $scale = null, bool $round = false): Money
    {
        $scale = null === $scale ? $this->scale : $scale;
        if ($round) {
            ++$scale;
        }
        $divisor = self::initNumeric($divisor, $scale);

        if (0 === bccomp($divisor, '0', $scale)) {
            throw new InvalidArgumentException('Divisor cannot be 0.');
        }

        $amount = bcdiv($this->amount, $divisor, $scale);
        $money = $this->newInstance($amount, $scale);

        return $round ?
            $money->round(--$scale) :
            $money
            ;
    }

    /**
     * Rounds this Money to another scale
     *
     * @param int $scale
     *
     * @return Money
     */
    public function round(int $scale = 0): Money
    {
        $add = '0.' . str_repeat('0', $scale) . '5';
        if ($this->isNegative()) {
            $add = '-' . $add;
        }
        $newAmount = bcadd($this->amount, $add, $scale);

        return $this->newInstance($newAmount, $scale);
    }

    /**
     * Converts the currency of this Money object to
     * a given target currency with a given conversion rate
     *
     * @param Currency $targetCurrency
     * @param int|float|string $conversionRate
     *
     * @return Money
     */
    public function convertTo(Currency $targetCurrency, $conversionRate): Money
    {
        $conversionRate = self::initNumeric($conversionRate, $this->scale);

        $amount = bcmul($this->amount, $conversionRate);

        return new self($amount, $targetCurrency, $this->scale);
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

    public function __toString(): string
    {
        // fill with 0s until scale
        $amount = bcadd($this->amount, '0', $this->scale);

        return "$amount:$this->currency";
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

        return bccomp($this->amount, $other->amount, $this->maxScale($other));
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
        return bccomp($this->amount, '0', $this->scale);
    }

    /**
     * Returns the largest scale between 2 Money objects
     *
     * @param Money $other
     * @return int
     */
    private function maxScale(Money $other): int
    {
        return max($this->scale, $other->scale);
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
     * @param int|float|string $value
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
