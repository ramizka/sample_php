<?php
declare(strict_types=1);

namespace Bseminar\Seminars\Price\Discounts;

use InvalidArgumentException;

abstract class AbstractDiscount implements DiscountInterface {

    /**
     * Initial price
     *
     * @var float
     */
    protected float $default_price = 0;

    /**
     * Price
     *
     * @var float
     */
    protected float $price = 0;

    /**
     * Cost
     *
     * @var float
     */
    protected float $cost = 0;

    /**
     * Applied discount
     *
     * @var float
     */
    protected float $discount = 0;

    /**
     * Discount rate
     *
     * @var float
     */
    protected float $rate = 0;

    /**
     * Quantity
     *
     * @var integer
     */
    protected int $quantity = 1;

    /**
     * Array of special discount data
     *
     * @var array
     */
    protected array $discountData = [];


    /**
     * Has discount
     *
     * @var bool
     */
    protected bool $discountApplied = false;


    /**
     * Construct discount object
     *
     * @param float $default_price
     * @param int $quantity
     * @param float $discount_rate
     * @param array $data
     * @param array $params
     */
    public function __construct(float $default_price, int $quantity, float $discount_rate, array $data = [], array $params = []){
        if ($default_price < 0){
            throw new InvalidArgumentException('Price must be greater or equal zero.');
        }
        $this->default_price = $default_price;


        if ($quantity <= 0){
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }
        $this->quantity = $quantity;

        if ($discount_rate <= 0){
            throw new InvalidArgumentException('Discount rate must be greater than zero.');
        }
        $this->rate = $discount_rate;

        $this->discountData = $data;

        $this->setData($data);
        $this->setParams($params);

        if ($this->calcDiscount()){
            $this->discountApplied = true;
            $this->prepareDiscountsData();
        }
    }

    /**
     * Get calculated price with discount
     *
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * Get calculated cost with discount
     *
     * @return float
     */
    public function getCost(): float
    {
        return $this->cost;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    /**
     * Get discounts data
     *
     * @return array
     */
    public function getDiscountsData(): array {
        return $this->discountData;
    }

    /**
     * Set discount params
     *
     * @param array $params
     * @return void
     */
    protected function setParams(array $params): void {}


    /**
     * Set discount data
     *
     * @param array $data
     * @return void
     */
    protected function setData(array $data): void {}


    /**
     * Calc price with discount, return true on success
     *
     * @return bool
     */
    protected function calcDiscount(): bool
    {
        if (!$this->isDiscountCanByApplied()){
            return false;
        }

        $this->discount = round(($this->default_price * $this->rate) / 100);
        $this->price = $this->default_price - $this->discount;
        $this->cost =  $this->price * $this->quantity;
        return true;
    }

    /**
     * Check if discount can be applied
     *
     * @return bool
     */
    abstract protected function isDiscountCanByApplied(): bool;

    public function getDiscountData(): array {
        return $this->discountData;
    }

    /**
     * Prepare discount data
     *
     * @return void
     */
    protected function prepareDiscountsData(): void {}

    /**
     * Check if discount can be applied
     *
     * @return bool
     */
    public function hasDiscount(): bool
    {
        return $this->discountApplied;
    }


}

