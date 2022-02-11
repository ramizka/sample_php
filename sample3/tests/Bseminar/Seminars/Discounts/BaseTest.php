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

    public function defaultDataProvider(): array
    {
        return [
            [5000, 2, 10, 5, true, 4500],
            [5000, 2, 3, 5, true, 4750],
        ];
    }
    /**
     * @dataProvider defaultDataProvider
     */
    public function testDefault(float $price, int $quantity, float $rate, float $minRate, bool $hasDiscount, float $discountedPrice): void
    {

        $discountObject = new \Bseminar\Seminars\Price\Discounts\Base($price, $quantity, $rate, [], [
                'minBaseDiscountRate' => $minRate,
            ]
        );

        $this->assertEquals($hasDiscount, $discountObject->hasDiscount(), 'Discount has not been applied');
        $this->assertEquals($discountedPrice, $discountObject->getPrice(),'Wrong price: should be '.$discountedPrice);
        $this->assertEquals($discountedPrice * 2, $discountObject->getCost(), 'Wrong cost: should be '.($discountedPrice * 2));

        $rateShouldBe = $rate < $minRate ? $minRate : $rate;

        $this->assertEquals($rateShouldBe, $discountObject->getRate(), 'Wrong discount rate: should be '.$rateShouldBe);
    }


}