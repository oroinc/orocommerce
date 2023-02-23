<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\Create;

use Oro\Bundle\ApiBundle\Processor\Create\SaveEntity;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Disable PersistEntity to fix creating ProductPrice with api when price sharding enabled. Disable process.
 */
class DisableProcessForPreventPersistEntity implements ProcessorInterface
{
    private bool $isEnablePriceSharding;

    public function __construct(bool $isEnablePriceSharding)
    {
        $this->isEnablePriceSharding = $isEnablePriceSharding;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        if ($this->isEnablePriceSharding) {
            /** @var SingleItemContext $context */
            $context->setProcessed(SaveEntity::OPERATION_NAME);
        }
    }
}
