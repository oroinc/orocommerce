<?php

namespace Oro\Bundle\RedirectBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\RedirectBundle\Api\Repository\RouteRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Loads a storefront route.
 */
class LoadRoute implements ProcessorInterface
{
    private RouteRepository $routeRepository;

    public function __construct(RouteRepository $slugRepository)
    {
        $this->routeRepository = $slugRepository;
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

        $route = $this->routeRepository->findRoute($context->getId());
        if (null === $route) {
            throw new NotFoundHttpException();
        }

        $context->setResult($route);
    }
}
