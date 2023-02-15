<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds ORM QueryBuilder object that will be used to get sub-resources for a master catalog tree node.
 */
class BuildCategoryNodeSubresourceQuery implements ProcessorInterface
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

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $context->setQuery(
            $this->doctrineHelper->createQueryBuilder(Category::class, 'e')
        );
    }
}
