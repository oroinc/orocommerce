<?php

namespace Oro\Bundle\PricingBundle\Api\ProductPrice\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Uses price manager to save product price
 */
class SaveProductPrice implements ProcessorInterface
{
    /**
     * @var PriceManager
     */
    private $priceManager;

    /**
     * @param PriceManager $priceManager
     */
    public function __construct(PriceManager $priceManager)
    {
        $this->priceManager = $priceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        $entity = $context->getResult();
        if (!$entity instanceof ProductPrice) {
            return;
        }

        $this->priceManager->persist($entity);
        $this->priceManager->flush();

        $context->setId($entity->getId());
    }
}
