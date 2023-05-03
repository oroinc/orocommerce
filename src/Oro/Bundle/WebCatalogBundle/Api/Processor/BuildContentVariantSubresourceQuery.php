<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\AddParentEntityIdToQuery;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds ORM QueryBuilder object that will be used to get the web catalog node content
 * if the "contentClass" attribute from the context is ORM entity.
 */
class BuildContentVariantSubresourceQuery implements ProcessorInterface
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

        $contentClass = $context->get(FindContentVariantForSubresource::CONTENT_CLASS);
        if (!$contentClass || !$this->doctrineHelper->isManageableEntityClass($contentClass)) {
            return;
        }

        $context->setQuery(
            $this->doctrineHelper
                ->createQueryBuilder($contentClass, 'e')
                ->where('e = :' . AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME)
                ->setParameter(
                    AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME,
                    $context->get(FindContentVariantForSubresource::CONTENT_ID)
                )
        );
    }
}
