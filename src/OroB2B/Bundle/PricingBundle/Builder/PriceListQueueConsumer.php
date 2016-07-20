<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceRuleChangeTriggerRepository;

class PriceListQueueConsumer
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var PriceListProductAssignmentBuilder
     */
    protected $productAssignmentBuilder;

    /**
     * @var ProductPriceBuilder
     */
    protected $productPriceBuilder;

    /**
     * @param ManagerRegistry $registry
     * @param PriceListProductAssignmentBuilder $productAssignmentBuilder
     * @param ProductPriceBuilder $productPriceBuilder
     */
    public function __construct(
        ManagerRegistry $registry,
        PriceListProductAssignmentBuilder $productAssignmentBuilder,
        ProductPriceBuilder $productPriceBuilder
    ) {
        $this->registry = $registry;
        $this->productAssignmentBuilder = $productAssignmentBuilder;
        $this->productPriceBuilder = $productPriceBuilder;
    }

    public function process()
    {
        $em = $this->registry->getManagerForClass(PriceRuleChangeTrigger::class);

        /** @var PriceRuleChangeTriggerRepository $repository */
        $repository = $em->getRepository(PriceRuleChangeTrigger::class);
        $iterator = $repository->getTriggersIterator();

        //TODO: use $trigger->getPriceList() as soon as it will be available
        foreach ($iterator as $trigger) {
            /** @var $trigger PriceRuleChangeTrigger */
            $this->productAssignmentBuilder->buildByPriceList($trigger->getPriceRule()->getPriceList());
            $this->productPriceBuilder->buildByPriceList(
                $trigger->getPriceRule()->getPriceList(),
                $trigger->getProduct()
            );
            $em->remove($trigger);
        }
        $em->flush();
    }
}
