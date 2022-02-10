<?php
declare(strict_types=1);

namespace Bseminar\Seminars\Price;

interface PriceInterface {

    /**
     * Get calculated price
     *
     * @return float
     */
    public function getPrice(): float;

    /**
     * Get calculated cost
     *
     * @return float
     */
    public function getCost(): float;


    /**
     * Get lowest price data
     *
     * @return array|null
     */
    public function getLowestData(): ?array;

    /**
     * Get lowest price type
     *
     * @return string|null
     */
    public function getLowestType(): ?string;

    /**
     * Return true|false if price is free
     *
     * @return bool
     */
    public function getPriceFree(): bool;

    /**
     * Get all available prices
     *
     * @return array
     */
    public function getAllAvailable(): array;

}