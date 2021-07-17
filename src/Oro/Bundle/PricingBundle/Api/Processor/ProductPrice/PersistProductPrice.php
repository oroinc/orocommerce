<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Registers a product price in the price manager.
 */
class PersistProductPrice implements ProcessorInterface
{
    /** @var PriceManager */
    private $priceManager;

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

        /** @var ProductPrice|null $entity */
        $entity = $context->getResult();
        if (null === $entity) {
            return;
        }

        $this->priceManager->persist($entity);
    }
}
