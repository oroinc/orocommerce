<?php

namespace Oro\Bundle\PricingBundle\Model\Step;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Step\AbstractStep;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Symfony\Bridge\Doctrine\RegistryInterface;

class SendPriceRecalculationMessageStep extends AbstractStep
{
    /**
     * @var PriceRuleLexemeTriggerHandler
     */
    private $lexemeTriggerHandler;

    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @var PriceListTriggerHandler
     */
    private $priceListTriggerHandler;

    /**
     * {@inheritDoc}
     */
    public function doExecute(StepExecution $stepExecution)
    {
        $priceList = $stepExecution->getJobExecution()->getExecutionContext()->get('price_list_id');
        $priceList = $this->registry
            ->getManagerForClass(PriceList::class)
            ->find(PriceList::class, $priceList);
        $lexemes = $this->lexemeTriggerHandler->findEntityLexemes(PriceList::class, ['prices'], $priceList->getId());
        $this->lexemeTriggerHandler->addTriggersByLexemes($lexemes);
        $this->priceListTriggerHandler->addTriggerForPriceList(Topics::RESOLVE_COMBINED_PRICES, $priceList);
        $this->priceListTriggerHandler->sendScheduledTriggers();
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function setConfiguration(array $config)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigurableStepElements()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param RegistryInterface $registry
     */
    public function setRegistry(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param PriceListTriggerHandler $priceListTriggerHandler
     */
    public function setPriceListTriggerHandler(PriceListTriggerHandler $priceListTriggerHandler)
    {
        $this->priceListTriggerHandler = $priceListTriggerHandler;
    }

    /**
     * @param PriceRuleLexemeTriggerHandler $lexemeTriggerHandler
     */
    public function setLexemeTriggerHandler(PriceRuleLexemeTriggerHandler $lexemeTriggerHandler)
    {
        $this->lexemeTriggerHandler = $lexemeTriggerHandler;
    }
}
