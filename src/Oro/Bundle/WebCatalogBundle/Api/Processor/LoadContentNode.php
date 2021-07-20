<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\WebCatalogBundle\Api\Repository\ContentNodeRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads a web catalog tree node.
 */
class LoadContentNode implements ProcessorInterface
{
    /** @var ContentNodeRepository */
    private $contentNodeRepository;

    public function __construct(ContentNodeRepository $contentNodeRepository)
    {
        $this->contentNodeRepository = $contentNodeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $context->setResult(
            $this->contentNodeRepository->getContentNode(
                $context->getId(),
                $context->getConfig(),
                $context->getNormalizationContext()
            )
        );

        // data are already normalized
        $context->skipGroup(ApiActionGroup::NORMALIZE_DATA);
    }
}
