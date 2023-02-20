<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\DoctrineHelper;

/**
 * Modifies the related product query to return only relations for which
 * both products in relation are accessible by an user.
 * Since Related Items Entities has no ACLs, we need to decide if relation should be showed to a user.
 * We are doing it by checking if both products in relation are accessible by an user.
 * We are using fact, that ACL is checked for all relations for entity (in that case for both products)
 * and if not Doctrine set them as NULL.
 */
class ProtectRelatedProductQueryByAcl implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if (!$context->hasQuery()) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $context->getQuery();
        $rootAlias = $this->doctrineHelper->getRootAlias($qb);
        $qb->leftJoin(sprintf('%s.product', $rootAlias), 'p')
            ->leftJoin(sprintf('%s.relatedItem', $rootAlias), 'ri')
            ->andWhere('p.id IS NOT NULL')
            ->andWhere('ri.id IS NOT NULL');
    }
}
