<?php

namespace Bseminar\Tests\Seminars\Discounts;

use \InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class QuantityTest extends TestCase
{

    public function testWrongDiscountQuantityException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('~Discount quantity~iu');
        $discountObject = new \Bseminar\Seminars\Price\Discounts\Quantity(5000, 1, 5, []);
    }

    public function testDefault(): void
    {

        $discountObject = new \Bseminar\Seminars\Price\Discounts\Quantity(5000, 5, 10, [
            'quantity' => 2,
            ]
        );


        $this->assertTrue($discountObject->hasDiscount(), 'Discount has not been applied');

        $this->assertEquals($discountObject->getPrice(), 4500, 'Wrong price: should be 4500');

        $this->assertEquals($discountObject->getCost(), 4500 * 5, 'Wrong cost: should be 9000');

        $discountObject = new \Bseminar\Seminars\Price\Discounts\Quantity(5000, 2, 10, [
            'quantity' => 50,
        ]);

        $this->assertFalse($discountObject->hasDiscount(), 'Discount should not be applied');
    }


}