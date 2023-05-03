<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateParentEntityAccess;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\CatalogBundle\Api\Repository\CategoryNodeRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Checks whether an VIEW access to the parent master catalog tree node is granted.
 */
class ValidateParentCategoryNodeAccess implements ProcessorInterface
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
        /** @var SubresourceContext $context */

        try {
            $parentNode = $this->categoryNodeRepository->getCategoryNodeEntity(
                $context->getParentId(),
                $context->getParentConfig(),
                $context->getRequestType()
            );
        } catch (AccessDeniedException $e) {
            throw new AccessDeniedException('No access to the parent entity.', $e);
        }
        if (null === $parentNode) {
            throw new NotFoundHttpException('The parent entity does not exist.');
        }

        $context->setProcessed(ValidateParentEntityAccess::OPERATION_NAME);
    }
}
