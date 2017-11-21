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

final class MoneyTest extends TestCase
{
    public function testFactoryMethod()
    {
        $money = Money::EUR(25);
        self::assertInstanceOf('Money\Money', $money);
    }

    public function testFromAmountAndCurrency()
    {
        $money = Money::fromAmount('100', Currency::fromCode('EUR'));
        self::assertInstanceOf('Money\Money', $money);
    }

    public function testNumericValues()
    {
        $money = Money::EUR('100');

        self::assertTrue($money->equals(Money::EUR(100)));
        self::assertTrue($money->equals(Money::EUR(100.00)));
        self::assertTrue($money->equals(Money::EUR('100.000000')));
    }

    public function testInitRoundingsOfFloatAndIntArguments()
    {
        self::assertEquals('0.0001', Money::EUR(0.00009)->amount());
        self::assertEquals('0.0000', Money::EUR(0)->amount());
        self::assertEquals('0.0000', Money::EUR('0.00009')->amount());
    }

    public function testScale()
    {
        self::assertSame('0.0000', Money::EUR(0)->amount());
        self::assertSame('0.0', Money::EUR(0, 1)->amount());
        self::assertSame('0.0000000000', Money::EUR(0, 10)->amount());
        self::assertSame('0.0100000000', Money::EUR(0.01, 10)->amount());
    }

    public function testNonNumericStringsThrowException()
    {
        self::expectException(InvalidArgumentException::class);

        Money::EUR('Foo');
    }

    public function testGetters()
    {
        $euro = Currency::fromCode('EUR');
        $money = Money::fromAmount('100', $euro);
        self::assertEquals('100', $money->amount());
        self::assertEquals($euro, $money->currency());
    }

    public function testAddition()
    {
        $m1 = Money::fromAmount('100', Currency::fromCode('EUR'));
        $m2 = Money::fromAmount('100', Currency::fromCode('EUR'));
        $sum = $m1->add($m2);
        $expected = Money::fromAmount('200', Currency::fromCode('EUR'));

        self::assertTrue($sum->equals($expected));

        // Should return a new instance
        self::assertNotSame($sum, $m1);
        self::assertNotSame($sum, $m2);
    }

    public function testAdditionWithDecimals()
    {
        $m1 = Money::fromAmount('100', Currency::fromCode('EUR'));
        $m2 = Money::fromAmount('0.01', Currency::fromCode('EUR'));
        $sum = $m1->add($m2);
        $expected = Money::fromAmount('100.01', Currency::fromCode('EUR'));

        self::assertTrue($sum->equals($expected));
    }

    public function testDifferentCurrenciesCannotBeAdded()
    {
        self::expectException(InvalidArgumentException::class);

        $m1 = Money::fromAmount('100', Currency::fromCode('EUR'));
        $m2 = Money::fromAmount('100', Currency::fromCode('USD'));
        $m1->add($m2);
    }

    public function testSubtraction()
    {
        $m1 = Money::fromAmount('100', Currency::fromCode('EUR'));
        $m2 = Money::fromAmount('200', Currency::fromCode('EUR'));
        $diff = $m1->subtract($m2);
        $expected = Money::fromAmount('-100', Currency::fromCode('EUR'));

        self::assertTrue($diff->equals($expected));

        // Should return a new instance
        self::assertNotSame($diff, $m1);
        self::assertNotSame($diff, $m2);
    }

    public function testSubtractionWithDecimals()
    {
        $m1 = Money::fromAmount('100.01', Currency::fromCode('EUR'));
        $m2 = Money::fromAmount('200', Currency::fromCode('EUR'));
        $diff = $m1->subtract($m2);
        $expected = Money::fromAmount('-99.99', Currency::fromCode('EUR'));

        self::assertTrue($diff->equals($expected));
    }

    public function testDifferentCurrenciesCannotBeSubtracted()
    {
        self::expectException(InvalidArgumentException::class);

        $m1 = Money::fromAmount('100', Currency::fromCode('EUR'));
        $m2 = Money::fromAmount('100', Currency::fromCode('USD'));
        $m1->subtract($m2);
    }

    public function testMultiplication()
    {
        $money = Money::fromAmount('100', Currency::fromCode('EUR'));

        self::assertSame('200.0000', $money->multiplyBy(2)->amount());
        self::assertSame('101.00', $money->multiplyBy('1.01', 2)->amount());

        self::assertNotSame($money, $money->multiplyBy(2));
    }

    public function testMultiplicationWithRounding()
    {
        $money = Money::fromAmount('0.3333', Currency::fromCode('EUR'));

        self::assertSame('0', $money->multiplyBy(3, 0, false)->amount());
        self::assertSame('1', $money->multiplyBy(3, 0, true)->amount());
    }

    public function testInvalidMultiplicationOperand()
    {
        self::expectException(InvalidArgumentException::class);

        $money = Money::fromAmount('100', Currency::fromCode('EUR'));
        $money->multiplyBy('operand');
    }

    public function testDivision()
    {
        $money = Money::fromAmount('30', Currency::fromCode('EUR'));

        self::assertSame('15.0000', $money->divideBy(2)->amount());
        self::assertSame('3.3333333333', $money->divideBy(9, 10)->amount());
        self::assertSame('-3.00', $money->divideBy(-10, 2)->amount());

        self::assertNotSame($money, $money->divideBy(2));
    }

    public function testDivisionWithRounding()
    {
        $money = Money::fromAmount('5.24', Currency::fromCode('EUR'));

        self::assertSame('0.0069866', $money->divideBy(750, 7)->amount());
        self::assertSame('0.0069867', $money->divideBy(750, 7, true)->amount());
    }

    public function testDivisorIsNumericZero()
    {
        self::expectException(InvalidArgumentException::class);

        $money = Money::fromAmount('30', Currency::fromCode('EUR'));
        $money->divideBy(0)->amount();
    }

    public function testDivisorIsFloatZero()
    {
        self::expectException(InvalidArgumentException::class);

        $money = Money::fromAmount('30', Currency::fromCode('EUR'));
        $money->divideBy(0.0)->amount();
    }

    public function testDivisorIsStringZero()
    {
        self::expectException(InvalidArgumentException::class);

        $money = Money::fromAmount('30', Currency::fromCode('EUR'));
        $money->divideBy('0')->amount();
    }

    public function testRoundWithoutRounding()
    {
        $money = Money::fromAmount('3.33333333333', Currency::fromCode('EUR'));
        $expected1 = Money::fromAmount('3', Currency::fromCode('EUR'));
        $expected2 = Money::fromAmount('3.33', Currency::fromCode('EUR'));

        self::assertTrue($money->round()->equals($expected1));
        self::assertTrue($money->round(2)->equals($expected2));

        self::assertNotSame($money, $money->round());
    }

    public function testRoundWithRounding()
    {
        $money = Money::fromAmount('3.9843', Currency::fromCode('EUR'));
        $expected1 = Money::fromAmount('4', Currency::fromCode('EUR'));
        $expected2 = Money::fromAmount('3.98', Currency::fromCode('EUR'));

        self::assertTrue($money->round()->equals($expected1));
        self::assertTrue($money->round(2)->equals($expected2));

        self::assertNotSame($money, $money->round());
    }

    public function testRoundWithNegativeAmountNoRounding()
    {
        $money = Money::fromAmount('-3.9813', Currency::fromCode('EUR'));
        self::assertSame('-3.98', $money->round(2)->amount());
    }

    public function testRoundWithNegativeAmountRounding()
    {
        $money = Money::fromAmount('-3.9863', Currency::fromCode('EUR'));
        self::assertSame('-3.99', $money->round(2)->amount());
    }

    public function testConvertTo()
    {
        $money = Money::fromAmount('100', Currency::fromCode('EUR'));
        $usd = Currency::fromCode('USD');

        $expected = Money::fromAmount('150', $usd);

        self::assertTrue($money->convertTo($usd, '1.50')->equals($expected));
    }

    public function testComparison()
    {
        $euro1 = Money::fromAmount('100', Currency::fromCode('EUR'));
        $euro2 = Money::fromAmount('200', Currency::fromCode('EUR'));
        $euro3 = Money::fromAmount('100', Currency::fromCode('EUR'));
        $euro4 = Money::fromAmount('0', Currency::fromCode('EUR'));
        $euro5 = Money::fromAmount('-100', Currency::fromCode('EUR'));
        $euro6 = Money::fromAmount('1.1111', Currency::fromCode('EUR'));
        $euro7 = Money::fromAmount('1.2222', Currency::fromCode('EUR'));

        self::assertTrue($euro2->isGreaterThan($euro1));
        self::assertFalse($euro1->isGreaterThan($euro2));
        self::assertTrue($euro1->isLessThan($euro2));
        self::assertFalse($euro2->isLessThan($euro1));
        self::assertTrue($euro1->equals($euro3));
        self::assertFalse($euro1->equals($euro2));
        self::assertFalse($euro6->equals($euro7));

        self::assertTrue($euro1->isGreaterThanOrEqualTo($euro3));
        self::assertTrue($euro1->isLessThanOrEqualTo($euro3));

        self::assertFalse($euro1->isGreaterThanOrEqualTo($euro2));
        self::assertFalse($euro1->isLessThanOrEqualTo($euro4));

        self::assertTrue($euro4->isLessThanOrEqualTo($euro1));
        self::assertTrue($euro4->isGreaterThanOrEqualTo($euro5));

        self::assertTrue($euro6->isLessThanOrEqualTo($euro7));
    }

    public function testPositivity()
    {
        $euro1 = Money::fromAmount('100', Currency::fromCode('EUR'));
        $euro2 = Money::fromAmount('0', Currency::fromCode('EUR'));
        $euro3 = Money::fromAmount('-100', Currency::fromCode('EUR'));
        $euro4 = Money::fromAmount('0.0001', Currency::fromCode('EUR'));

        self::assertTrue($euro1->isPositive());
        self::assertFalse($euro1->isNegative());
        self::assertFalse($euro1->isZero());

        self::assertTrue($euro2->isZero());
        self::assertFalse($euro2->isNegative());
        self::assertFalse($euro2->isPositive());

        self::assertTrue($euro3->isNegative());
        self::assertFalse($euro3->isPositive());
        self::assertFalse($euro3->isZero());

        self::assertFalse($euro4->isZero());
    }

    public function testDifferentCurrenciesCannotBeCompared()
    {
        self::expectException(InvalidArgumentException::class);

        Money::EUR(1)->equals(Money::USD(1));
    }

    public function testHasSameCurrencyAs()
    {
        self::assertTrue(Money::EUR(1)->hasSameCurrencyAs(Money::EUR(100)));
        self::assertTrue(Money::EUR(1)->hasSameCurrencyAs(Money::EUR(1)));
        self::assertFalse(Money::EUR(1)->hasSameCurrencyAs(Money::USD(1)));
    }
}
