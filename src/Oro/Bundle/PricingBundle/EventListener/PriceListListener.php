<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use Oro\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler;

class PriceListListener
{
    const IS_ACTIVE_FIELD = 'isActive';

    /**
     * @var PriceListSchedule[]
     */
    protected $priceListSchedules = [];

    /**
     * @var CombinedPriceListActivationPlanBuilder
     */
    protected $activationPlanBuilder;

    /**
     * @var PriceListChangeTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var array
     */
    protected $plDataBeforeUpdate = [];

    /**
     * @var PriceRuleLexemeHandler
     */
    protected $priceRuleLexemeHandler;

    /**
     * @param CombinedPriceListActivationPlanBuilder $activationPlanBuilder
     * @param PriceListChangeTriggerHandler $triggerHandler
     * @param PriceRuleLexemeHandler $priceRuleLexemeHandler
     */
    public function __construct(
        CombinedPriceListActivationPlanBuilder $activationPlanBuilder,
        PriceListChangeTriggerHandler $triggerHandler,
        PriceRuleLexemeHandler $priceRuleLexemeHandler
    ) {
        $this->activationPlanBuilder = $activationPlanBuilder;
        $this->triggerHandler = $triggerHandler;
        $this->priceRuleLexemeHandler = $priceRuleLexemeHandler;
    }

    /**
     * @param FormProcessEvent $event
     */
    public function beforeSubmit(FormProcessEvent $event)
    {
        /** @var PriceList $priceList */
        $priceList = $event->getData();

        $this->plDataBeforeUpdate[$priceList->getId()][self::IS_ACTIVE_FIELD] = $priceList->isActive();

        foreach ($priceList->getSchedules() as $schedule) {
            $this->priceListSchedules[] = $schedule->getHash();
        }
    }

    /**
     * @param AfterFormProcessEvent $event
     */
    public function onPostSubmit(AfterFormProcessEvent $event)
    {
        /** @var PriceList $priceList */
        $priceList = $event->getData();
        $this->priceRuleLexemeHandler->updateLexemes($priceList);
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

        if (array_key_exists($priceList->getId(), $this->plDataBeforeUpdate)
            && array_key_exists(self::IS_ACTIVE_FIELD, $this->plDataBeforeUpdate[$priceList->getId()])
            && $this->plDataBeforeUpdate[$priceList->getId()][self::IS_ACTIVE_FIELD] !== $priceList->isActive()
        ) {
            $this->triggerHandler->handlePriceListStatusChange($priceList);
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
