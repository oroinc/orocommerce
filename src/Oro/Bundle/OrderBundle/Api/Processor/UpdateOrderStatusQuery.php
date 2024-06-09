<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Updates the DQL query to load the internal order status instead of the order status
 * when "Enable External Status Management" configuration option is disabled.
 */
class UpdateOrderStatusQuery implements ProcessorInterface
{
    private OrderConfigurationProviderInterface $configurationProvider;

    public function __construct(OrderConfigurationProviderInterface $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $query = $context->getQuery();
        if (!$query instanceof QueryBuilder) {
            return;
        }

        if ($this->configurationProvider->isExternalStatusManagementEnabled()) {
            return;
        }

        $entityAlias = QueryBuilderUtil::getSingleRootAlias($query);
        $query->resetDQLPart('from');
        $query->from('Extend\Entity\EV_Order_Internal_Status', $entityAlias);
    }
}
