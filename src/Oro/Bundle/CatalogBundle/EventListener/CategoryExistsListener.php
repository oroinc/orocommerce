<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;

class CategoryExistsListener
{
    // listener should run only on this route
    const PRODUCT_INDEX_ROUTE = 'oro_product_frontend_product_index';

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param FilterControllerEvent $event
     * @throws NotFoundHttpException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $route   = $request->attributes->get('_route', null);

        if ($route === null || $route !== static::PRODUCT_INDEX_ROUTE) {
            return;
        }

        $categoryExistsInUri = $request->get(RequestProductHandler::CATEGORY_ID_KEY, null) !== null;

        if (!$categoryExistsInUri) {
            return;
        }

        /** @var Category $category */
        $category = $this->categoryRepository->find((int)$request->get(RequestProductHandler::CATEGORY_ID_KEY));

        if ($category === null) {
            throw new NotFoundHttpException(sprintf(
                'Category %s has not been found',
                $request->get(RequestProductHandler::CATEGORY_ID_KEY)
            ));
        }
    }
}
