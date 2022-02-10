<?php

declare(strict_types=1);

namespace Bseminar\Seminars\Price;

use InvalidArgumentException;

class PriceWithDiscount implements PriceInterface {

    private const EVENT_TYPES = ['common', 'online', 'distance', 'distance-date', 'corporate'];


    /**
     * Minimum base discount rate
     *
     * @var float
     */
    private float $minBaseDiscountRate;

    /**
     * Quantity
     *
     * @var int
     */
    private int $quantity;

    /**
     * Price in rubles
     *
     * @var float
     */
    private float $price;

    /**
     * Cost
     *
     * @var float
     */
    protected float $cost = 0;

    /**
     * Event type
     *
     * @var string|null
     */
    private ?string $eventType = null;

    /**
     * Is price free?
     *
     * @var bool
     */
    private bool $priceFree = false;


    /**
     * Array of discounts
     *
     * @var array
     */
    private array $discounts = [];

    /**
     * Array of params
     *
     * @var array
     */
    private array $params;


    /**
     * All available prices
     *
     * @var array
     */
    private array $availablePrices = [];

    /**
     * Discount factory
     *
     * @var DiscountFactory
     */
    private DiscountFactory $discountFactory;


    public function __construct (float $price, int $quantity, array $params = []){
        if ($price < 0){
            throw new InvalidArgumentException('Price must be greater or equal zero.');
        }
        $this->price = $price;


        if ($quantity <= 0){
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }
        $this->quantity = $quantity;

        if (!isset($params['minBaseDiscountRate'])) {
            throw new InvalidArgumentException('Base minimum discount rate must be set');
        }

        $minBaseDiscountRate = (float) $params['minBaseDiscountRate'];
        if ($minBaseDiscountRate < 0){
            throw new InvalidArgumentException('Base minimum discount rate must be greater than zero');
        }

        $this->minBaseDiscountRate = $minBaseDiscountRate;

        foreach ($params as $param => $paramValue) {
            switch ($param){
                case 'eventType':
                    $this->setEventType($paramValue);
                    unset($params[$param]);
                    break;
            }
        }

        $this->setDiscountFactory($params['discountFactory'] ?? null);
        unset($params['discountFactory']);

        $this->setDiscounts($params['discounts'] ?? []);
        unset($params['discounts']);

        $this->setPriceFree($params['priceFree'] ?? false);
        unset($params['priceFree']);



        $this->params = $params;

        $this->calculate();
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCost(): float
    {
        return $this->cost;
    }

    /**
     * Get the lowest price data
     *
     * @return array|null
     */
    public function getLowestData(): ?array
    {
        return $this->availablePrices[0] ?? null;
    }

    /**
     * Get the lowest price type
     *
     * @return string|null
     */
    public function getLowestType(): ?string {
        return $this->availablePrices[0]['type'] ?? null;
    }

    private function setDiscountFactory(?DiscountFactoryInterface $discountFactory): void
    {
        if ($discountFactory === null){
            $discountFactory = new DiscountFactory();
        }

        $this->discountFactory = $discountFactory;
    }


    /**
     * Set discounts
     *
     * @param array $discounts
     */
    private function setDiscounts(array $discounts = []): void {
        $hasDefault = false;
        foreach ($discounts as &$discountData){

            if (empty($discountData['type'])){
                throw new InvalidArgumentException('Discount type is empty');
            }
            $discountData['type'] = strtolower($discountData['type']);

            if (!$this->discountFactory->isDiscountValid($discountData['type'])){
                throw new InvalidArgumentException('Wrong discount type: '.$discountData['type']);
            }

            if ($discountData['type'] === 'default'){
                $hasDefault = true;
            }

        }
        unset($discountData);

        if (!$hasDefault){
            $discounts []= [
                "type" => "default",
                "rate" => $this->minBaseDiscountRate,
                "default" => true
            ];
        }

        $this->discounts = $discounts;
    }

    /**
     * Set event type
     *
     * @param string|null $eventType
     * @return void
     */
    private function setEventType(?string $eventType): void {
        if ($eventType){
            if (!in_array($eventType, static::EVENT_TYPES)){
                throw new InvalidArgumentException('Wrong event type: '.$eventType);
            }
            $this->eventType = $eventType;
        }
    }

    /**
     * Set true if price is free
     *
     * @param bool $priceFree
     * @return void
     */
    private function setPriceFree(bool $priceFree): void
    {
        $this->priceFree = $this->price === .0 ? true : $priceFree;
    }

    /**
     * Return true|false if price is free
     *
     * @return bool
     */
    public function getPriceFree(): bool
    {
        return $this->priceFree;
    }

    public function getAllAvailable(): array
    {
        return $this->availablePrices;
    }

    private function calcDiscounts(): void
    {
        foreach ($this->discounts as $discountData){
            $discountClass = $this->discountFactory->getDiscount($discountData['type'], $this->price, $this->quantity, $discountData['rate'] ?? 0, $discountData, $this->params);
            if ($discountClass->hasDiscount()){
                $this->setPrice(
                    $discountData['type'],
                    $discountClass->getPrice(),
                    $discountClass->getCost(),
                    $discountClass->getDiscount(),
                    $discountClass->getDiscountData()
                );
            }
        }
    }

    /**
     * Set price data
     *
     * @param string $priceType
     * @param float $price
     * @param float $cost
     * @param float $discount
     * @param array $data
     */
    private function setPrice(string $priceType, float $price, float $cost, float $discount, array $data = []): void
    {
        $this->availablePrices[] = $data + [
            'type' => $priceType,
            'price' => $price,
            'cost' => $cost,
            'discount' => $discount
        ];
    }

    private function setDefaultPrice(): void
    {
        $hasDefaultPrice = false;
        foreach ($this->availablePrices as $priceData){
            if ($priceData['type'] === 'default'){
                $hasDefaultPrice = true;
                break;
            }
        }

        if (!$hasDefaultPrice){
            $this->setPrice('default', $this->price, $this->price * $this->quantity, 0, ['default' => true]);
        }
    }


    /**
     * Calculate price
     *
     */
    private function calculate(): void
    {
        if ($this->eventType === 'corporate'){
            $this->price = 0;
            return;
        }

        if ($this->price === 0.0){
            $this->setPriceFree(true);
        }
        $this->calcDiscounts();
        $this->setDefaultPrice();

        usort($this->availablePrices, static function($a, $b){
            return $a["cost"] <=> $b["cost"];
        });

        $lowestData = $this->getLowestData();

        $this->price = $lowestData['price'];
        $this->cost = $lowestData['cost'];
    }

}