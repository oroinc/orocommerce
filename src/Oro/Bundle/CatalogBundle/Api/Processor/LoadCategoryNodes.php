<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\CatalogBundle\Api\Repository\CategoryNodeRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads master catalog tree nodes.
 */
class LoadCategoryNodes implements ProcessorInterface
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
        /** @var ListContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $criteria = $context->getCriteria();
        if (!$criteria instanceof Criteria) {
            // unsupported criteria
            return;
        }

        $context->setResult(
            $this->categoryNodeRepository->getCategoryNodes(
                $criteria,
                $context->getConfig(),
                $context->getNormalizationContext()
            )
        );

        // data are already normalized
        $context->skipGroup(ApiActionGroup::NORMALIZE_DATA);
    }
}
