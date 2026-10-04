<?php

use App\Support\Money;

test('it holds minor units and reports the major amount', function () {
    $money = Money::fromMinorUnits(1999);

    expect($money->minorUnits)->toBe(1999)
        ->and($money->toDecimal())->toBe(19.99)
        ->and($money->currency)->toBe('USD');
});

test('it builds from a decimal string without floating point drift', function () {
    expect(Money::fromDecimal('19.99')->minorUnits)->toBe(1999)
        ->and(Money::fromDecimal('0.07')->minorUnits)->toBe(7)
        ->and(Money::fromDecimal('1234.56')->minorUnits)->toBe(123456);
});

test('adding amounts does not drift the way floats do', function () {
    $total = Money::fromDecimal('0.10')->plus(Money::fromDecimal('0.20'));

    expect($total->minorUnits)->toBe(30)
        ->and($total->toDecimal())->toBe(0.30);
});

test('it adds, subtracts and scales', function () {
    $ten = Money::fromDecimal('10.00');

    expect($ten->plus(Money::fromDecimal('2.50'))->minorUnits)->toBe(1250)
        ->and($ten->minus(Money::fromDecimal('2.50'))->minorUnits)->toBe(750)
        ->and($ten->multipliedBy(3)->minorUnits)->toBe(3000)
        ->and($ten->multipliedBy(0.175)->minorUnits)->toBe(175);
});

test('scaling rounds to the nearest minor unit', function () {
    expect(Money::fromMinorUnits(333)->multipliedBy(1 / 3)->minorUnits)->toBe(111)
        ->and(Money::fromMinorUnits(1)->multipliedBy(0.5)->minorUnits)->toBe(1);
});

test('it refuses to combine different currencies', function () {
    Money::fromDecimal('1.00', 'USD')->plus(Money::fromDecimal('1.00', 'EUR'));
})->throws(InvalidArgumentException::class);

test('it rejects a currency that is not an alpha-3 code', function () {
    new Money(100, 'DOLLARS');
})->throws(InvalidArgumentException::class);

test('it reports margin over a cost as a percentage of the price', function () {
    $price = Money::fromDecimal('25.00');

    expect($price->marginOver(Money::fromDecimal('10.00')))->toBe(60.0)
        ->and($price->marginOver(Money::fromDecimal('25.00')))->toBe(0.0);
});

test('margin is negative when the product sells below cost', function () {
    expect(Money::fromDecimal('10.00')->marginOver(Money::fromDecimal('12.00')))->toBeLessThan(0);
});

test('margin is undefined when the price is zero', function () {
    expect(Money::zero()->marginOver(Money::fromDecimal('5.00')))->toBeNull();
});

test('it compares by amount and currency', function () {
    expect(Money::fromDecimal('1.00')->equals(Money::fromDecimal('1.00')))->toBeTrue()
        ->and(Money::fromDecimal('1.00')->equals(Money::fromDecimal('1.00', 'EUR')))->toBeFalse()
        ->and(Money::fromDecimal('1.00')->equals(Money::fromDecimal('2.00')))->toBeFalse();
});

test('it formats and stringifies with the currency symbol', function () {
    expect(Money::fromDecimal('19.99')->format())->toBe('$19.99')
        ->and((string) Money::fromDecimal('5.00'))->toBe('$5.00');
});

test('zero reports itself as zero and not positive', function () {
    expect(Money::zero()->isZero())->toBeTrue()
        ->and(Money::zero()->isPositive())->toBeFalse()
        ->and(Money::fromDecimal('0.01')->isPositive())->toBeTrue();
});
