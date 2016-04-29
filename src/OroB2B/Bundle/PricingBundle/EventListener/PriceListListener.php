<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListSchedule;

class PriceListListener
{
    /**
     * @var PriceListSchedule[]
     */
    protected $priceListSchedules = [];

    /**
     * @var CombinedPriceListActivationPlanBuilder
     */
    protected $activationPlanBuilder;

    /**
     * @param CombinedPriceListActivationPlanBuilder $activationPlanBuilder
     */
    public function __construct(CombinedPriceListActivationPlanBuilder $activationPlanBuilder)
    {
        $this->activationPlanBuilder = $activationPlanBuilder;
    }

    /**
     * @param FormProcessEvent $event
     */
    public function beforeSubmit(FormProcessEvent $event)
    {
        /** @var PriceList $priceList */
        $priceList = $event->getData();
        foreach ($priceList->getSchedules() as $schedule) {
            $this->priceListSchedules[] = $schedule->getHash();
        }
    }

    /**
     * @param AfterFormProcessEvent $event
     */
    public function afterFlush(AfterFormProcessEvent $event)
    {
        /** @var PriceList $priceList */
        $priceList = $event->getData();

        if ($priceList->getId() && $this->isCollectionChanged($priceList)) {
            $this->activationPlanBuilder->buildByPriceList($priceList);
        }
    }

    /**
     * @param PriceList $priceList
     * @return bool
     */
    protected function isCollectionChanged(PriceList $priceList)
    {
        if (count($this->priceListSchedules) !== $priceList->getSchedules()->count()) {
            return true;
        }

        $submitted = array_map(
            function (PriceListSchedule $item) {
                return $item->getHash();
            },
            $priceList->getSchedules()->toArray()
        );

        foreach ($this->priceListSchedules as $existing) {
            if (!in_array($existing, $submitted, true)) {
                return true;
            }
        }

        return false;
    }
}
