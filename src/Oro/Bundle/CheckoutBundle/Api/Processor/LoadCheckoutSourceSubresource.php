<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\LoadCustomAssociationUtil;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * Loads data for "source" association of Checkout entity using the EntitySerializer component.
 * As returned data is already normalized, the "normalize_data" group will be skipped.
 */
class LoadCheckoutSourceSubresource implements ProcessorInterface
{
    public function __construct(
        private readonly EntitySerializer $entitySerializer,
        private readonly DoctrineHelper $doctrineHelper,
        private readonly ConfigProvider $configProvider
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $context->setResult($this->loadData($context));

        // data returned by the EntitySerializer are already normalized
        $context->skipGroup(ApiActionGroup::NORMALIZE_DATA);
    }

    private function loadData(SubresourceContext $context): ?array
    {
        $qb = $this->doctrineHelper->createQueryBuilder($context->getParentClassName(), 'e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $context->getParentId());

        $data = $this->entitySerializer->serialize(
            $qb,
            LoadCustomAssociationUtil::buildParentEntityConfig($context, $this->configProvider),
            $context->getNormalizationContext()
        );

        return $data[0][$context->getAssociationName()] ?: null;
    }
}
