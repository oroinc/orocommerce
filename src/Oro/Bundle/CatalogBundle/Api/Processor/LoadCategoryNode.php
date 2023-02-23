<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\CatalogBundle\Api\Repository\CategoryNodeRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads a master catalog tree node.
 */
class LoadCategoryNode implements ProcessorInterface
{
    private CategoryNodeRepository $categoryNodeRepository;

    public function __construct(CategoryNodeRepository $categoryNodeRepository)
    {
        $this->categoryNodeRepository = $categoryNodeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $context->setResult(
            $this->categoryNodeRepository->getCategoryNode(
                $context->getId(),
                $context->getConfig(),
                $context->getNormalizationContext()
            )
        );

        // data are already normalized
        $context->skipGroup(ApiActionGroup::NORMALIZE_DATA);
    }
}
