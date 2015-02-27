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

/**
 * @coversDefaultClass Money\Currency
 * @uses Money\Currency
 * @uses Money\Money
 */
final class CurrencyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $currency = Currency::fromCode('EUR');

        $this->assertEquals('EUR', $currency->code());
    }

    /**
     * @covers ::code
     * @covers ::__toString
     */
    public function testCode()
    {
        $currency = Currency::fromCode('EUR');
        $this->assertEquals('EUR', $currency->code());
        $this->assertEquals('EUR', (string) $currency);
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
        $this->assertTrue($c1->equals($c2));
        $this->assertTrue($c3->equals($c4));
    }

    /**
     * @covers ::equals
     */
    public function testDifferentCurrenciesAreNotEqual()
    {
        $c1 = Currency::fromCode('EUR');
        $c2 = Currency::fromCode('USD');
        $this->assertFalse($c1->equals($c2));
    }
}
