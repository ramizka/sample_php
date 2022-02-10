<?php
declare(strict_types=1);

namespace Bseminar\Seminars\Price\Discounts;

class Quantity extends AbstractDiscount {

    /**
     * Quantity for discount to by applied
     *
     * @var int
     */
    private int $discountQuantity = 0;

    protected function setData(array $data): void
    {
        $quantity = $data['quantity'] ?? 0;

        $quantity = (int) $quantity;
        if ($quantity <= 0){
            throw new \InvalidArgumentException('Discount quantity must be greater than zero');
        }

        $this->discountQuantity = $quantity;
    }

    protected function isDiscountCanByApplied(): bool
    {
        return !($this->discountQuantity === 0 || $this->discountQuantity > $this->quantity);
    }


}