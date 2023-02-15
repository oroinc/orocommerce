<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\WebCatalogBundle\Api\Repository\SystemPageRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Loads system page content variant data for "get_subresource" and "get_subresource" actions.
 */
class LoadSystemPageContentVariantSubresource implements ProcessorInterface
{
    private SystemPageRepository $systemPageRepository;

    public function __construct(SystemPageRepository $systemPageRepository)
    {
        $this->systemPageRepository = $systemPageRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $systemPage = $this->systemPageRepository->findSystemPage(
            $context->get(FindContentVariantForSubresource::CONTENT_ID)
        );
        if (!$systemPage) {
            throw new NotFoundHttpException();
        }

        $context->setResult($systemPage);
    }
}
