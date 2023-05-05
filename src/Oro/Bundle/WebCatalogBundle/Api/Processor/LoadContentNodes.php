<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\WebCatalogBundle\Api\Repository\ContentNodeRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads web catalog tree nodes.
 */
class LoadContentNodes implements ProcessorInterface
{
    private ContentNodeRepository $contentNodeRepository;

    public function __construct(ContentNodeRepository $contentNodeRepository)
    {
        $this->contentNodeRepository = $contentNodeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $query = $context->getQuery();
        if (!$query instanceof QueryBuilder) {
            // unsupported query
            return;
        }

        $context->setResult(
            $this->contentNodeRepository->getContentNodes(
                $query,
                $context->getConfig(),
                $context->getNormalizationContext()
            )
        );

        // data are already normalized
        $context->skipGroup(ApiActionGroup::NORMALIZE_DATA);
    }
}
