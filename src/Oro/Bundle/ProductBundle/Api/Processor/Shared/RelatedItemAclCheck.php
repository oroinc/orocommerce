<?php

namespace Oro\Bundle\ProductBundle\Api\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\DoctrineHelper;

class RelatedItemAclCheck implements ProcessorInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     *
     * Since Related Items Entities has no ACLs, we need to decide if relation should be showed to a user.
     * We are doing it by checking if both products in relation are accessible by an user.
     * We are using fact, that ACL is checked for all relations for entity (in that case for both products) and if not
     * Doctrine set them as NULL.
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */
        if (!$context->hasQuery()) {
            return;
        }

        $qb = $context->getQuery();

        $rootAlias = $this->doctrineHelper->getRootAlias($qb);

        $qb->leftJoin(sprintf('%s.product', $rootAlias), 'p')
            ->leftJoin(sprintf('%s.relatedItem', $rootAlias), 'ri')
            ->andWhere('p.id IS NOT NULL')
            ->andWhere('ri.id IS NOT NULL');
    }
}
