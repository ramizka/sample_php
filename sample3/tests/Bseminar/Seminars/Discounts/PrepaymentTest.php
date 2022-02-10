<?php

namespace Bseminar\Tests\Seminars\Discounts;

use \InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PrepaymentTest extends TestCase
{

    public function testWrongPriceException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('~price~iu');
        $discountObject = new \Bseminar\Seminars\Price\Discounts\Prepayment(-1, 1, 5);
    }

    public function testWrongQuantityException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('~quantity~iu');
        $discountObject = new \Bseminar\Seminars\Price\Discounts\Prepayment(5000, -1, 5);
    }

    public function testWrongRateException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('~rate~iu');
        $discountObject = new \Bseminar\Seminars\Price\Discounts\Prepayment(5000, 1, -1);
    }

    public function testWrongDaysException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('~Days~iu');
        $discountObject = new \Bseminar\Seminars\Price\Discounts\Prepayment(5000, 1, 5, []);
    }


    public function testDefault(): void
    {

        $discountObject = new \Bseminar\Seminars\Price\Discounts\Prepayment(5000, 2, 10, [
            'days' => 5,
            ],
            [
            'dateNow' => '2018-01-01',
            'dateStart' => '2018-02-01'
            ]
        );


        $this->assertTrue($discountObject->hasDiscount(), 'Discount has not been applied');

        $this->assertEquals($discountObject->getPrice(), 4500, 'Wrong price: should be 4500');

        $this->assertEquals($discountObject->getCost(), 4500 * 2, 'Wrong cost: should be 9000');

        $discountObject = new \Bseminar\Seminars\Price\Discounts\Prepayment(5000, 2, 10, [
            'days' => 50
            ], [
            'dateNow' => '2018-01-01',
            'dateStart' => '2018-02-01'
        ]);

        $this->assertFalse($discountObject->hasDiscount(), 'Discount should not be applied');
    }


}