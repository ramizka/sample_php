<?php
declare(strict_types=1);

namespace Bseminar\Seminars\Price\Discounts;

interface DiscountInterface {

    /**
     * Get calculated price with discount
     *
     * @return float
     */
    public function getPrice(): float;

    /**
     * Get calculated cost with discount
     *
     * @return float
     */
    public function getCost(): float;


    /**
     * Get calculated discount
     *
     * @return float
     */
    public function getDiscount(): float;

    /**
     * Get discount rate
     *
     * @return float
     */
    public function getRate(): float;


    /**
     * Get discounts data
     *
     * @return array
     */
    public function getDiscountsData(): array;

    /**
     * Return special discount data
     *
     * @return array
     */
    public function getDiscountData(): array;

    /**
     * Check if discount can be applied
     *
     * @return bool
     */
    public function hasDiscount(): bool;

}

