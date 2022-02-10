<?php
declare(strict_types=1);

namespace Bseminar\Seminars\Price;

use Bseminar\Seminars\Price\Discounts\DiscountInterface;

interface DiscountFactoryInterface {

    /**
     * Get discount object by its name
     *
     * @param string $discountName
     * @param ...$args
     * @return DiscountInterface
     */
    public function getDiscount(string $discountName, ...$args): DiscountInterface;

    /**
     * Return true if discount name is valid
     *
     * @param string $discountName
     * @return bool
     */
    public function isDiscountValid(string $discountName): bool;
}