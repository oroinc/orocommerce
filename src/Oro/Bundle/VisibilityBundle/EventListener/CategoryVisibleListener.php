<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\VisibilityBundle\Visibility\Checker\FrontendCategoryVisibilityCheckerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Restricts access to the category based on its visibility settings.
 */
class CategoryVisibleListener implements ServiceSubscriberInterface
{
    private ManagerRegistry $doctrine;
    private ContainerInterface $container;

    public function __construct(ManagerRegistry $doctrine, ContainerInterface $container)
    {
        $this->doctrine = $doctrine;
        $this->container = $container;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [FrontendCategoryVisibilityCheckerInterface::class];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if ('oro_product_frontend_product_index' !== $event->getRequest()->attributes->get('_route')) {
            return;
        }
        $categoryId = $event->getRequest()->get(RequestProductHandler::CATEGORY_ID_KEY);
        if (null === $categoryId) {
            return;
        }

        $category = $this->doctrine->getManagerForClass(Category::class)->find(Category::class, (int)$categoryId);
        if (null === $category || !$this->getCategoryVisibilityChecker()->isCategoryVisible($category)) {
            throw new NotFoundHttpException(sprintf('The category %s was not found.', $categoryId));
        }
    }

    private function getCategoryVisibilityChecker(): FrontendCategoryVisibilityCheckerInterface
    {
        return $this->container->get(FrontendCategoryVisibilityCheckerInterface::class);
    }
}
