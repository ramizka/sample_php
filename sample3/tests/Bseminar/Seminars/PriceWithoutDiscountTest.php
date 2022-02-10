<?php

namespace Bseminar\Tests\Seminars;

use \InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PriceWithoutDiscountTest extends TestCase
{

    public function testWrongPriceException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('~price~iu');
        $priceObject = new \Bseminar\Seminars\Price\PriceWithDiscount(-1, 1, ['minBaseDiscountRate' => 5]);
    }

    public function testWrongQuantityException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('~quantity~iu');
        $priceObject = new \Bseminar\Seminars\Price\PriceWithDiscount(5000, 0, ['minBaseDiscountRate' => 5]);
    }

    public function testNoMinBaseDiscountRateException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('~discount rate~iu');
        $priceObject = new \Bseminar\Seminars\Price\PriceWithDiscount(5000, 1, []);
    }

    public function testMinBaseDiscountRateException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('~discount rate must be greater~iu');
        $priceObject = new \Bseminar\Seminars\Price\PriceWithDiscount(5000, 1, ['minBaseDiscountRate' => -1]);
    }

    public function testInvalidDiscounts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('~discount type~iu');
        $priceObject = new \Bseminar\Seminars\Price\PriceWithDiscount(5000, 1, ['minBaseDiscountRate' => 5, 'discounts' => ['type' => 'sss']]);
    }

    public function testNoPriceOnCorporateEvent(): void
    {
        $priceObject = new \Bseminar\Seminars\Price\PriceWithDiscount(5000, 1, ['minBaseDiscountRate' => 5, 'eventType' => 'corporate']);
        $this->assertNull($priceObject->getLowestType());
    }

    public function testDefault(): void
    {
        $priceObject = new \Bseminar\Seminars\Price\PriceWithDiscount(5000, 2, [
            'minBaseDiscountRate' => 5,
            'eventType' => 'online',
            'discounts' => [
                [
                    'type' => 'default',
                    'rate' => 6,
                ],
                [
                    'type' => 'quantity',
                    'quantity' => 3,
                    'rate' => 10,
                ],
                [
                    'type' => 'prepayment',
                    'rate' => 15,
                    'days' => 10
                ]
            ],
            'dateNow' => '2018-01-01',
            'dateStart' => '2018-02-01'
        ]);


        $this->assertEquals($priceObject->getLowestType(), 'prepayment', 'Wrong price type: should by prepayment');

        $this->assertFalse($priceObject->getPriceFree(), 'Wrong price free: should by false');

        $this->assertEquals($priceObject->getPrice(), 4250, 'Price should by 4250 rub');

        $hasDiscountQuantity = false;
        foreach ($priceObject->getAllAvailable() as $priceData){
            if ($priceData['type'] === 'quantity'){
                $hasDiscountQuantity = true;
                break;
            }
        }

        $this->assertFalse($hasDiscountQuantity, 'We should not have discounts by quantity');
    }

}