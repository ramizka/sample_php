<?php
declare(strict_types=1);

namespace Bseminar\Seminars\Price\Discounts;

use InvalidArgumentException;

class Base extends AbstractDiscount {

    /**
     * Base discount
     *
     * @var float
     */
    private float $minBaseDiscountRate = 0;

    protected function setParams(array $params): void
    {
        if (!empty($params['minBaseDiscountRate'])) {
            $minRate = (float) $params['minBaseDiscountRate'];
            if ($minRate <= 0){
                throw new InvalidArgumentException('Base minimum discount rate must be greater than zero');
            }
            $this->minBaseDiscountRate = $minRate;
        }

        if ($this->rate < $this->minBaseDiscountRate){
            $this->rate = $this->minBaseDiscountRate;
        }
    }

    protected function isDiscountCanByApplied(): bool
    {
        return true;
    }


}