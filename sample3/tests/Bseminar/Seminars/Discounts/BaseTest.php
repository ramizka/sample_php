<?php

namespace Bseminar\Tests\Seminars\Discounts;

use \InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BaseTest extends TestCase
{

    public function testMinBaseDiscountRateException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('~Base minimum discount~iu');
        $discountObject = new \Bseminar\Seminars\Price\Discounts\Base(5000, 1, 10, [], ['minBaseDiscountRate' => -1]);
    }

    public function testDefault(): void
    {

        $discountObject = new \Bseminar\Seminars\Price\Discounts\Base(5000, 2, 10, [], [
            'minBaseDiscountRate' => 5,
            ]
        );


        $this->assertTrue($discountObject->hasDiscount(), 'Discount has not been applied');

        $this->assertEquals($discountObject->getPrice(), 4500, 'Wrong price: should be 4500');

        $this->assertEquals($discountObject->getCost(), 4500 * 2, 'Wrong cost: should be 9000');

        $this->assertEquals($discountObject->getRate(), 10, 'Wrong discount rate: should be 10');


        $discountObject = new \Bseminar\Seminars\Price\Discounts\Base(5000, 2, 3, [], [
                'minBaseDiscountRate' => 5,
            ]
        );


        $this->assertEquals($discountObject->getRate(), 5, 'Wrong discount rate: should be 5');
    }


}