<?php
declare(strict_types=1);

namespace Bseminar\Seminars\Price\Discounts;

use DateInterval;
use DateTime;
use Exception;
use InvalidArgumentException;

class Prepayment extends AbstractDiscount {

    /**
     * What date today
     *
     * @var DateTime
     */
    private DateTime $dateNow;

    /**
     * Days before event should start
     *
     * @var int
     */
    private int $daysBeforeEventStart = 0;

    /**
     * Start date of event
     *
     * @var DateTime
     */
    private DateTime $dateStart;

    /**
     * Minimum days till event start when payment is done
     *
     * @var int
     */
    private int $daysBeforePay = 0;

    protected function setParams(array $params): void
    {
        $this->setDateNow($params['dateNow'] ?? null);
        $this->setDateStart($params['dateStart'] ?? null);
    }

    /**
     * Set today
     *
     * @param string|null $now
     * @return Prepayment
     * @throws Exception
     */
    public function setDateNow(?string $now = null): self {
        $this->dateNow = new DateTime($now ?? 'now');
        $this->dateNow->setTime(0, 0);
        return $this;
    }

    protected function setData(array $data): void
    {
        $this->setDays($data['days'] ? (int) $data['days'] : 0);
        $this->setDateStart($params['dateStart'] ?? null);
    }


    /**
     * Set days before full payments
     *
     * @param int $days
     * @return $this
     */
    public function setDays(int $days): self
    {
        if ($days <= 0){
            throw new InvalidArgumentException('Days should be greater than zero');
        }

        $this->daysBeforePay = $days;

        return $this;
    }

    /**
     * Set event start date
     *
     * @param string|null $dateStart
     * @return $this
     * @throws Exception
     */
    public function setDateStart(?string $dateStart = null): self
    {
        if ($dateStart){
            $this->dateStart = new DateTime($dateStart);
            $this->dateStart->setTime(0, 0);

            $interval = $this->dateStart->diff($this->dateNow, true);
            $this->daysBeforeEventStart = (int) $interval->format('%a');
        }
        return $this;
    }

    protected function isDiscountCanByApplied(): bool
    {
        if (empty($this->daysBeforeEventStart) || empty($this->daysBeforePay))
        {
            return false;
        }

        if ($this->daysBeforePay <= $this->daysBeforeEventStart){
            return true;
        }

        return false;
    }

    protected function prepareDiscountsData(): void
    {
        $daysBeforePay = $this->getDatePayBefore();
        if ($daysBeforePay){
            $this->discountData['daysBeforePay'] = $daysBeforePay;
        }
    }

    /**
     * Get maximum date when client should pay for its event
     *
     * @return DateTime|null
     * @throws Exception
     */
    private function getDatePayBefore(): ?DateTime
    {
        if (empty($this->dateStart) || empty($this->daysBeforePay)){
            return null;
        }
        $datePayBefore = clone $this->dateStart;
        $datePayBefore->sub(new DateInterval("P".$this->daysBeforePay."D"));
        return $datePayBefore;
    }

}