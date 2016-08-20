<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleChangeTriggerRepository;

class PriceRuleQueueConsumer
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

        foreach ($iterator as $trigger) {
            /** @var $trigger PriceRuleChangeTrigger */
            $this->productAssignmentBuilder->buildByPriceList($trigger->getPriceList());
            $this->productPriceBuilder->buildByPriceList(
                $trigger->getPriceList(),
                $trigger->getProduct()
            );
            $em->remove($trigger);
        }
        $em->flush();
    }
}
