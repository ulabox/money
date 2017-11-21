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

final class CurrencyTest extends TestCase
{
    public function testConstructor()
    {
        $currency = Currency::fromCode('EUR');

        self::assertEquals('EUR', $currency->code());
    }

    public function testCode()
    {
        $currency = Currency::fromCode('EUR');
        self::assertEquals('EUR', $currency->code());
        self::assertEquals('EUR', (string) $currency);
    }

    public function testDifferentInstancesAreEqual()
    {
        $c1 = Currency::fromCode('EUR');
        $c2 = Currency::fromCode('EUR');
        $c3 = Currency::fromCode('USD');
        $c4 = Currency::fromCode('USD');
        self::assertTrue($c1->equals($c2));
        self::assertTrue($c3->equals($c4));
    }

    public function testDifferentCurrenciesAreNotEqual()
    {
        $c1 = Currency::fromCode('EUR');
        $c2 = Currency::fromCode('USD');
        self::assertFalse($c1->equals($c2));
    }

    public function testToUpper()
    {
        $c1 = Currency::fromCode('EUR');
        $c2 = Currency::fromCode('eur');
        self::assertTrue($c1->equals($c2));
    }

    public function testNonStringCode()
    {
        self::expectException(InvalidArgumentException::class);

        Currency::fromCode(1234);
    }

    public function testNon3LetterCode()
    {
        self::expectException(InvalidArgumentException::class);

        Currency::fromCode('FooBar');
    }
}
