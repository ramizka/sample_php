<?php
declare(strict_types=1);

namespace Bseminar\Seminars\Price;

use Bseminar\Seminars\Price\Discounts\DiscountInterface;

class DiscountFactory implements DiscountFactoryInterface {

    protected const DISCOUNT_CLASSES = [
        'default' => Discounts\Base::class,
        'prepayment' => Discounts\Prepayment::class,
        'quantity' => Discounts\Quantity::class,
    ];

    /**
     * Get discount object by its name
     *
     * @param string $discountName
     * @param ...$args
     * @return DiscountInterface
     */
    public function getDiscount(string $discountName, ...$args): DiscountInterface {
        $discountName = strtolower($discountName);

        if (!isset(static::DISCOUNT_CLASSES[$discountName])){
            throw new \InvalidArgumentException('Wrong discount type: '.$discountName);
        }

        $className = static::DISCOUNT_CLASSES[$discountName];

        return new $className(...$args);
    }

    /**
     * Return true if discount name is valid
     *
     * @param string $discountName
     * @return bool
     */
    public function isDiscountValid(string $discountName): bool {
        $discountName = strtolower($discountName);
        return isset(static::DISCOUNT_CLASSES[$discountName]);
    }
}