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

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Money\Currency
 * @uses Money\Currency
 * @uses Money\Money
 */
final class CurrencyTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $currency = Currency::fromCode('EUR');

        self::assertEquals('EUR', $currency->code());
    }

    /**
     * @covers ::code
     * @covers ::__toString
     */
    public function testCode()
    {
        $currency = Currency::fromCode('EUR');
        self::assertEquals('EUR', $currency->code());
        self::assertEquals('EUR', (string) $currency);
    }

    /**
     * @covers ::equals
     */
    public function testDifferentInstancesAreEqual()
    {
        $c1 = Currency::fromCode('EUR');
        $c2 = Currency::fromCode('EUR');
        $c3 = Currency::fromCode('USD');
        $c4 = Currency::fromCode('USD');
        self::assertTrue($c1->equals($c2));
        self::assertTrue($c3->equals($c4));
    }

    /**
     * @covers ::equals
     */
    public function testDifferentCurrenciesAreNotEqual()
    {
        $c1 = Currency::fromCode('EUR');
        $c2 = Currency::fromCode('USD');
        self::assertFalse($c1->equals($c2));
    }

    /**
     * @covers ::equals
     */
    public function testToUpper()
    {
        $c1 = Currency::fromCode('EUR');
        $c2 = Currency::fromCode('eur');
        self::assertTrue($c1->equals($c2));
    }

    /**
     * @expectedException Money\InvalidArgumentException
     */
    public function testNonStringCode()
    {
        Currency::fromCode(1234);
    }

    /**
     * @expectedException Money\InvalidArgumentException
     */
    public function testNon3LetterCode()
    {
        Currency::fromCode('FooBar');
    }
}
